import { FaceDetector, FilesetResolver } from '@mediapipe/tasks-vision';

export const directions = {
    front: 'Look straight at the camera.',
    left: 'Slowly turn toward your left shoulder.',
    right: 'Slowly turn toward your right shoulder.',
    up: 'Tilt your chin slightly up.',
    down: 'Tilt your chin slightly down.',
};

export async function createDetector() {
    const runtime = await FilesetResolver.forVisionTasks('/face-guidance/v1');
    return FaceDetector.createFromOptions(runtime, {
        baseOptions: { modelAssetPath: '/face-guidance/v1/blaze-face-short-range.tflite' },
        runningMode: 'VIDEO',
        minDetectionConfidence: 0.70,
    });
}

function poseFromEyes(keypoints, width, height) {
    if (keypoints.length < 4) return null;
    const [first, second, nose, mouth] = keypoints;
    const [left, right] = first.x < second.x ? [first, second] : [second, first];
    const dx = (right.x - left.x) * width;
    const dy = (right.y - left.y) * height;
    const distance = Math.hypot(dx, dy);
    const eyeX = (left.x + right.x) / 2;
    const eyeY = (left.y + right.y) / 2;
    const perpendicular = point => ((point.y - eyeY) * height * dx - (point.x - eyeX) * width * dy) / distance;
    const mouthHeight = perpendicular(mouth);
    if (distance < 16 || Math.abs(dy / distance) > 0.29 || mouthHeight < 12) return null;
    return {
        yaw: ((nose.x - eyeX) * width * dx + (nose.y - eyeY) * height * dy) / distance ** 2,
        pitch: perpendicular(nose) / mouthHeight,
    };
}

function inspectFace(canvas, ctx, detection) {
    const box = detection.boundingBox;
    if (!box) return { message: 'Center your face in the oval.' };
    const { width, height } = canvas;
    const { originX: x, originY: y, width: w, height: h } = box;
    if (w < Math.max(72, width * 0.25) || h < Math.max(72, height * 0.29)) {
        return { message: 'Move a little closer.' };
    }
    if (x < 0 || y < 0 || x + w > width || y + h > height || w > width * 0.84 || h > height * 0.89) {
        return { message: 'Move back so your whole face is visible.' };
    }
    const cx = (x + w / 2) / width;
    const cy = (y + h / 2) / height;
    if (Math.abs(cx - 0.5) > 0.17 || Math.abs(cy - 0.5) > 0.20) {
        return { message: 'Center your face in the oval.' };
    }

    const { data } = ctx.getImageData(Math.floor(x), Math.floor(y), Math.floor(w), Math.floor(h));
    const stride = Math.floor(w) * 4;
    const luminance = index => data[index] * 0.299 + data[index + 1] * 0.587 + data[index + 2] * 0.114;
    let count = 0, sum = 0, squared = 0, detail = 0;
    for (let row = 1; row < Math.floor(h) - 1; row += 4) {
        for (let col = 1; col < Math.floor(w) - 1; col += 4) {
            const index = row * stride + col * 4;
            const level = luminance(index);
            const edge = 4 * level - luminance(index - 4) - luminance(index + 4)
                - luminance(index - stride) - luminance(index + stride);
            count++;
            sum += level;
            squared += level * level;
            detail += edge * edge;
        }
    }
    const brightness = sum / count;
    if (brightness < 49) return { message: 'Add light in front of your face.' };
    if (brightness > 219) return { message: 'Reduce the light on your face.' };
    if (squared / count - brightness ** 2 < 196) return { message: 'Use even light from the front.' };
    if (detail / count < 24) return { message: 'Hold still so the camera can focus.' };
    const pose = poseFromEyes(detection.keypoints || [], width, height);
    return pose ? { ...pose, cx, cy } : { message: 'Keep your head level and both eyes visible.' };
}

export function matchesPose(sample, pose, front) {
    if (!front) return Math.abs(sample.yaw) <= 0.12 && sample.pitch >= 0.35 && sample.pitch <= 0.73;
    const yaw = sample.yaw - front.yaw;
    const pitch = sample.pitch - front.pitch;
    switch (pose) {
        case 'front': return Math.abs(yaw) <= 0.075 && Math.abs(pitch) <= 0.06;
        case 'left': return yaw >= 0.17 && yaw <= 0.59 && Math.abs(pitch) <= 0.12;
        case 'right': return yaw <= -0.17 && yaw >= -0.59 && Math.abs(pitch) <= 0.12;
        case 'up': return pitch <= -0.12 && pitch >= -0.31 && Math.abs(yaw) <= 0.15;
        case 'down': return pitch >= 0.12 && pitch <= 0.31 && Math.abs(yaw) <= 0.15;
        default: return false;
    }
}

export function captureSteps(poses) {
    return poses.flatMap((pose, index) => index ? [{ pose: 'front', count: 1, transition: true }, { pose, count: 2 }] : [{ pose, count: 2 }]);
}

function drawPreview(video, canvas, ctx) {
    const viewportAspect = video.clientWidth / video.clientHeight || 1;
    const width = Math.min(video.videoWidth, video.videoHeight * viewportAspect);
    const height = Math.min(video.videoHeight, video.videoWidth / viewportAspect);
    const scale = Math.min(1, 480 / Math.max(width, height));
    const targetWidth = Math.round(width * scale);
    const targetHeight = Math.round(height * scale);
    if (Math.min(targetWidth, targetHeight) < 100) throw new Error('The camera resolution is too low. Use another verification method.');
    if (canvas.width !== targetWidth || canvas.height !== targetHeight) {
        canvas.width = targetWidth;
        canvas.height = targetHeight;
    }
    ctx.drawImage(video, (video.videoWidth - width) / 2, (video.videoHeight - height) / 2,
        width, height, 0, 0, targetWidth, targetHeight);
}

export async function captureSequence({ video, canvas, detector, poses, signal, expiresIn, onGuide, onProgress }) {
    const ctx = canvas.getContext('2d', { willReadFrequently: true });
    if (!ctx) throw new Error('Camera capture is unavailable. Use another verification method.');
    const deadline = performance.now() + expiresIn * 1000;
    let front = null;
    let completed = 0;
    let previousVideoTime = -1;
    const captures = [];
    for (const step of captureSteps(poses)) {
        let stableSince = 0;
        let last = null;
        let lastCapture = 0;
        const frames = [];
        const instruction = step.transition ? 'Return to the center.' : directions[step.pose];
        onGuide('Looking for your face.', 'searching', instruction);
        while (frames.length < step.count) {
            await new Promise(resolve => setTimeout(resolve, 135));
            signal.throwIfAborted();
            const now = performance.now();
            if (now >= deadline) throw new Error('The camera check timed out. Try again or ask your teacher for help.');
            if (video.readyState < 2 || video.currentTime === previousVideoTime) continue;
            previousVideoTime = video.currentTime;
            drawPreview(video, canvas, ctx);
            const { detections } = detector.detectForVideo(canvas, now);
            const sample = detections.length === 1 ? inspectFace(canvas, ctx, detections[0]) : {
                message: detections.length > 1 ? 'Only one person should be in the frame.' : 'Center your face in the oval.',
            };
            if (sample.message || !matchesPose(sample, step.pose, front)) {
                stableSince = 0;
                last = null;
                frames.length = 0;
                onGuide(sample.message || 'Face detected.', sample.message ? 'searching' : 'detected', instruction);
                continue;
            }

            if (!last || Math.hypot(sample.cx - last.cx, sample.cy - last.cy) > 0.035
                || Math.abs(sample.yaw - last.yaw) > 0.05 || Math.abs(sample.pitch - last.pitch) > 0.05) {
                stableSince = now;
                frames.length = 0;
            }
            last = sample;
            onGuide('Face detected. Hold steady.', 'detected', instruction);
            if (now - stableSince < 430 || now - lastCapture < 190) continue;
            const frame = canvas.toDataURL('image/jpeg', 0.81);
            if (!frame.startsWith('data:image/jpeg;base64,') || frame.length > 240000) {
                throw new Error('The camera frame could not be captured. Try again or use device verification.');
            }
            frames.push(frame);
            lastCapture = now;
        }
        if (!front) front = last;
        captures.push(...frames);
        if (!step.transition) {
            completed++;
            onProgress(completed, step.pose);
        }
    }
    return captures;
}
