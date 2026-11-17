(function (root, factory) {
    const api = factory();
    if (typeof module === 'object' && module.exports) {
        module.exports = api;
    }
    if (root) {
        root.GpsSampler = api;
    }
})(typeof globalThis !== 'undefined' ? globalThis : this, function () {
    'use strict';

    const EARTH_RADIUS_METERS = 6371000;

    function normalizeCoordinates(lat, lng) {
        let latitude = Number(lat);
        let longitude = Number(lng);
        if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) {
            return null;
        }

        // Correct only an impossible latitude; never guess based on a region.
        if (Math.abs(latitude) > 90 && Math.abs(latitude) <= 180 && Math.abs(longitude) <= 90) {
            const temp = latitude;
            latitude = longitude;
            longitude = temp;
        }

        if (Math.abs(latitude) > 90 || Math.abs(longitude) > 180) {
            return null;
        }

        return [latitude, longitude];
    }

    function distanceMeters(lat1, lng1, lat2, lng2) {
        const pointA = normalizeCoordinates(lat1, lng1);
        const pointB = normalizeCoordinates(lat2, lng2);
        if (!pointA || !pointB) return Infinity;
        const [aLat, aLng] = pointA;
        const [bLat, bLng] = pointB;
        const toRad = deg => deg * Math.PI / 180;
        const dLat = toRad(bLat - aLat);
        const dLng = toRad(bLng - aLng);
        const sinLat = Math.sin(dLat / 2);
        const sinLng = Math.sin(dLng / 2);
        const haversine = (sinLat * sinLat)
            + Math.cos(toRad(aLat)) * Math.cos(toRad(bLat)) * (sinLng * sinLng);
        const clamped = Math.min(1, Math.max(0, haversine));
        return EARTH_RADIUS_METERS * 2 * Math.atan2(Math.sqrt(clamped), Math.sqrt(1 - clamped));
    }

    function normalizeSample(input) {
        if (!input || typeof input !== 'object') return null;
        const source = input.coords && typeof input.coords === 'object' ? input.coords : input;
        const lat = Number(source.latitude !== undefined ? source.latitude : source.lat);
        const lng = Number(source.longitude !== undefined ? source.longitude : source.lng);
        const acc = Number(source.accuracy !== undefined ? source.accuracy : source.acc);
        const rawTs = input.timestamp !== undefined ? input.timestamp : source.timestamp;
        if (rawTs === undefined || rawTs === null || !Number.isFinite(Number(rawTs))) return null;
        const timestamp = Number(rawTs);

        if (!Number.isFinite(lat) || Math.abs(lat) > 90) return null;
        if (!Number.isFinite(lng) || Math.abs(lng) > 180) return null;
        if (!Number.isFinite(acc) || acc <= 0) return null;

        return { lat, lng, acc, timestamp };
    }

    function isValidSample(input, options) {
        const opts = options || {};
        const nowMs = opts.nowMs !== undefined ? opts.nowMs : Date.now();
        const maxAgeMs = opts.maxAgeMs !== undefined ? opts.maxAgeMs : 12000;
        const maxFutureMs = opts.maxFutureMs !== undefined ? opts.maxFutureMs : 5000;
        const maxAccuracy = opts.maxAccuracy !== undefined ? opts.maxAccuracy : Infinity;

        const sample = normalizeSample(input);
        if (!sample) return false;
        if (sample.acc > maxAccuracy) return false;

        const age = nowMs - sample.timestamp;
        return age >= -maxFutureMs && age <= maxAgeMs;
    }

    function buildEstimate(cluster, totalSamples, bestRawAcc, consistent) {
        let sumW = 0;
        let sumLat = 0;
        let sumLng = 0;
        let minAcc = Infinity;
        let newestTs = 0;

        for (let i = 0; i < cluster.length; i++) {
            const s = cluster[i];
            const weight = 1 / (Math.max(4, s.acc) ** 2);
            sumW += weight;
            sumLat += s.lat * weight;
            sumLng += s.lng * weight;
            if (s.acc < minAcc) minAcc = s.acc;
            if (s.timestamp > newestTs) newestTs = s.timestamp;
        }

        const lat = sumLat / sumW;
        const lng = sumLng / sumW;

        let fusedAcc = minAcc;
        if (cluster.length > 1) {
            let maxDist = 0;
            for (let i = 0; i < cluster.length; i++) {
                const dist = distanceMeters(lat, lng, cluster[i].lat, cluster[i].lng);
                if (dist > maxDist) maxDist = dist;
            }
            const standardError = 1 / Math.sqrt(sumW);
            const conservativeAcc = Math.max(minAcc * 0.85, Math.hypot(standardError, maxDist * 0.5));
            // Correlated GPS readings must not make the reported uncertainty
            // look better than the strongest individual sensor fix.
            fusedAcc = Math.round(Math.max(minAcc, conservativeAcc) * 10) / 10;
        }

        return {
            lat,
            lng,
            acc: fusedAcc,
            latitude: lat,
            longitude: lng,
            accuracy: fusedAcc,
            bestRawAcc,
            timestamp: newestTs,
            sampleCount: cluster.length,
            totalSamples,
            consistent,
            clustered: cluster.length >= 2,
            toGeolocationPosition() {
                return {
                    coords: {
                        latitude: this.lat,
                        longitude: this.lng,
                        accuracy: this.acc,
                    },
                    timestamp: this.timestamp,
                };
            },
        };
    }

    function fuseCluster(samples, options) {
        const opts = options || {};
        const minGateMeters = opts.minGateMeters !== undefined ? opts.minGateMeters : 12;
        const maxClusterWindowMs = opts.maxClusterWindowMs !== undefined ? opts.maxClusterWindowMs : 20000;
        const maxAccuracy = opts.maxAccuracy !== undefined ? opts.maxAccuracy : Infinity;
        const maxSpreadMeters = opts.maxSpreadMeters !== undefined ? opts.maxSpreadMeters : Infinity;

        const normalized = [];
        for (let i = 0; i < (samples || []).length; i++) {
            const item = normalizeSample(samples[i]);
            if (item && item.acc <= maxAccuracy) {
                normalized.push(item);
            }
        }
        if (!normalized.length) return null;

        let bestRawAcc = Infinity;
        for (let i = 0; i < normalized.length; i++) {
            if (normalized[i].acc < bestRawAcc) bestRawAcc = normalized[i].acc;
        }

        if (normalized.length === 1) {
            return buildEstimate([normalized[0]], 1, bestRawAcc, true);
        }

        let bestCluster = null;
        let bestScore = -Infinity;
        let bestClusterTime = -Infinity;

        for (let i = 0; i < normalized.length; i++) {
            const anchor = normalized[i];
            const group = [anchor];
            for (let j = 0; j < normalized.length; j++) {
                if (i === j) continue;
                const peer = normalized[j];
                if (Math.abs(anchor.timestamp - peer.timestamp) > maxClusterWindowMs) continue;
                const gate = Math.min(maxSpreadMeters, Math.max(minGateMeters, anchor.acc, peer.acc));
                if (distanceMeters(anchor.lat, anchor.lng, peer.lat, peer.lng) <= gate) {
                    group.push(peer);
                }
            }
            if (group.length < 2) continue;

            let weightScore = 0;
            let newest = 0;
            for (let k = 0; k < group.length; k++) {
                weightScore += 1 / (Math.max(4, group[k].acc) ** 2);
                if (group[k].timestamp > newest) newest = group[k].timestamp;
            }

            if (weightScore > bestScore + 1e-9 || (Math.abs(weightScore - bestScore) <= 1e-9 && newest > bestClusterTime)) {
                bestScore = weightScore;
                bestCluster = group;
                bestClusterTime = newest;
            }
        }

        if (bestCluster) {
            return buildEstimate(bestCluster, normalized.length, bestRawAcc, true);
        }

        const sorted = normalized.slice().sort((a, b) => {
            if (a.acc !== b.acc) return a.acc - b.acc;
            return b.timestamp - a.timestamp;
        });

        return buildEstimate([sorted[0]], normalized.length, bestRawAcc, false);
    }

    function shouldReplaceContinuousFix(currentFix, candidateInput, options) {
        const opts = options || {};
        const staleAfterMs = opts.staleAfterMs !== undefined ? opts.staleAfterMs : 15000;
        const maxAcceptableAccuracy = opts.maxAcceptableAccuracy !== undefined ? opts.maxAcceptableAccuracy : 50;
        const coarseExpiryMs = opts.coarseExpiryMs !== undefined ? opts.coarseExpiryMs : 30000;
        const nowMs = opts.nowMs !== undefined ? opts.nowMs : Date.now();

        const candidate = normalizeSample(candidateInput);
        if (!candidate || !isValidSample(candidate, { nowMs, maxAgeMs: staleAfterMs, maxFutureMs: 5000 })) return false;

        const current = normalizeSample(currentFix);
        if (!current) return true;

        if (candidate.timestamp < current.timestamp) return false;
        const ageMs = candidate.timestamp - current.timestamp;
        if (current.acc <= maxAcceptableAccuracy) {
            if (candidate.acc > maxAcceptableAccuracy) {
                return ageMs > coarseExpiryMs;
            }
            return candidate.acc <= current.acc * 1.15 || ageMs > staleAfterMs;
        }

        return candidate.acc <= current.acc || ageMs > 5000;
    }

    function mergeContinuousSamples(buffer, candidateInput, options) {
        const opts = options || {};
        const maxBufferSize = opts.maxBufferSize !== undefined ? opts.maxBufferSize : 5;
        const windowMs = opts.windowMs !== undefined ? opts.windowMs : 15000;
        const maxAcceptableAccuracy = opts.maxAcceptableAccuracy !== undefined ? opts.maxAcceptableAccuracy : 50;
        const nowMs = opts.nowMs !== undefined ? opts.nowMs : Date.now();

        const candidate = normalizeSample(candidateInput);
        const existing = (Array.isArray(buffer) ? buffer : []).filter(item =>
            isValidSample(item, { nowMs, maxAgeMs: windowMs, maxFutureMs: 5000 })
        );
        if (!candidate || !isValidSample(candidate, { nowMs, maxAgeMs: windowMs, maxFutureMs: 5000 })) {
            const fallbackEstimate = fuseCluster(existing, { maxClusterWindowMs: windowMs });
            return { buffer: existing, estimate: fallbackEstimate, accepted: false };
        }

        const freshBuffer = existing.filter(item =>
            Math.abs(candidate.timestamp - item.timestamp) <= windowMs
            && !(item.timestamp === candidate.timestamp && item.lat === candidate.lat && item.lng === candidate.lng)
        );
        const currentGood = freshBuffer.filter(item => item.acc <= maxAcceptableAccuracy);

        // Do not dilute an active precise fix buffer with a sudden coarse fix.
        if (currentGood.length > 0 && candidate.acc > maxAcceptableAccuracy) {
            const estimate = fuseCluster(currentGood, { maxClusterWindowMs: windowMs });
            return { buffer: freshBuffer, estimate, accepted: false };
        }

        freshBuffer.push(candidate);
        freshBuffer.sort((a, b) => {
            if (a.acc <= maxAcceptableAccuracy && b.acc > maxAcceptableAccuracy) return -1;
            if (b.acc <= maxAcceptableAccuracy && a.acc > maxAcceptableAccuracy) return 1;
            if (Math.abs(a.timestamp - b.timestamp) > 6000) return b.timestamp - a.timestamp;
            return a.acc - b.acc;
        });

        const trimmed = freshBuffer.slice(0, maxBufferSize);
        const acceptable = trimmed.filter(item => item.acc <= maxAcceptableAccuracy);
        const estimate = acceptable.length
            ? fuseCluster(acceptable, { maxClusterWindowMs: windowMs })
            : fuseCluster(trimmed, { maxClusterWindowMs: windowMs });

        return { buffer: trimmed, estimate, accepted: true };
    }

    function capturePosition(options) {
        const opts = options || {};
        const geo = opts.geolocation || (typeof navigator !== 'undefined' ? navigator.geolocation : null);
        const timeoutMs = opts.timeoutMs !== undefined ? opts.timeoutMs : 14000;
        const settleMs = opts.settleMs !== undefined ? opts.settleMs : 3000;
        const maxAgeMs = opts.maxAgeMs !== undefined ? opts.maxAgeMs : 12000;
        const targetAccuracy = opts.targetAccuracy !== undefined ? opts.targetAccuracy : 15;
        const maxAcceptableAccuracy = opts.maxAcceptableAccuracy !== undefined ? opts.maxAcceptableAccuracy : 50;
        const minClusterSamples = opts.minClusterSamples !== undefined ? opts.minClusterSamples : 2;
        const maxSamples = opts.maxSamples !== undefined ? opts.maxSamples : 5;
        const singleImmediateAccuracy = opts.singleImmediateAccuracy !== undefined ? opts.singleImmediateAccuracy : 0;
        const requireClusterIfDisagreeing = Boolean(opts.requireClusterIfDisagreeing);
        const allowSingleFallback = opts.allowSingleFallback !== undefined ? opts.allowSingleFallback : true;
        const fallbackToBestCoarse = Boolean(opts.fallbackToBestCoarse);
        const maxSpreadMeters = opts.maxSpreadMeters !== undefined ? opts.maxSpreadMeters : Infinity;
        const onSample = typeof opts.onSample === 'function' ? opts.onSample : null;

        if (!geo || (typeof geo.watchPosition !== 'function' && typeof geo.getCurrentPosition !== 'function')) {
            return Promise.resolve({
                ok: false,
                fix: null,
                bestRawAccuracy: null,
                sampleCount: 0,
                reason: 'unsupported',
                error: new Error('Geolocation not supported'),
            });
        }

        return new Promise((resolve) => {
            let finished = false;
            let watchId = null;
            let timeoutTimer = null;
            let settleTimer = null;
            let bestRawAccuracy = Infinity;
            let bestCoarseSample = null;
            let lastError = null;
            const acceptableReadings = [];

            const finalizeFromReadings = (timedOut) => {
                const rawAcc = Number.isFinite(bestRawAccuracy) ? bestRawAccuracy : null;
                if (acceptableReadings.length > 0) {
                    const estimate = fuseCluster(acceptableReadings, { maxSpreadMeters });
                    if (acceptableReadings.length > 1 && !estimate.clustered && requireClusterIfDisagreeing) {
                        return {
                            ok: false,
                            fix: null,
                            bestRawAccuracy: rawAcc,
                            sampleCount: acceptableReadings.length,
                            reason: 'unstable',
                            error: lastError,
                        };
                    }
                    if (!allowSingleFallback && estimate.sampleCount < minClusterSamples) {
                        return {
                            ok: false,
                            fix: null,
                            bestRawAccuracy: rawAcc,
                            sampleCount: estimate.sampleCount,
                            reason: estimate.consistent ? 'insufficient_samples' : 'unstable',
                            error: lastError,
                        };
                    }
                    return {
                        ok: true,
                        fix: estimate,
                        bestRawAccuracy: rawAcc,
                        sampleCount: estimate.sampleCount,
                        reason: estimate.clustered ? 'clustered' : 'single_fallback',
                        error: null,
                    };
                }

                if (fallbackToBestCoarse && bestCoarseSample) {
                    const coarseEstimate = buildEstimate([bestCoarseSample], 1, bestCoarseSample.acc, false);
                    return {
                        ok: true,
                        fix: coarseEstimate,
                        bestRawAccuracy: rawAcc,
                        sampleCount: 1,
                        reason: 'coarse_fallback',
                        error: null,
                    };
                }

                return {
                    ok: false,
                    fix: null,
                    bestRawAccuracy: rawAcc,
                    sampleCount: 0,
                    reason: rawAcc !== null ? 'low_accuracy' : 'timeout',
                    error: lastError,
                };
            };

            const finish = (outcome) => {
                if (finished) return;
                finished = true;
                if (timeoutTimer !== null) clearTimeout(timeoutTimer);
                if (settleTimer !== null) clearTimeout(settleTimer);
                if (watchId !== null && typeof geo.clearWatch === 'function') {
                    try {
                        geo.clearWatch(watchId);
                    } catch (_) {}
                }
                resolve(outcome);
            };

            const handlePosition = (pos) => {
                const nowMs = Date.now();
                if (!isValidSample(pos, { nowMs, maxAgeMs })) return;
                const sample = normalizeSample(pos);
                if (!sample) return;

                if (sample.acc < bestRawAccuracy) {
                    bestRawAccuracy = sample.acc;
                }
                if (!bestCoarseSample || sample.acc < bestCoarseSample.acc) {
                    bestCoarseSample = sample;
                }

                if (sample.acc > maxAcceptableAccuracy) {
                    if (onSample) {
                        onSample({
                            latest: sample,
                            estimate: acceptableReadings.length ? fuseCluster(acceptableReadings, { maxSpreadMeters }) : null,
                            bestRawAccuracy,
                            sampleCount: acceptableReadings.length,
                        });
                    }
                    return;
                }

                if (!acceptableReadings.some(prev => prev.timestamp === sample.timestamp && prev.lat === sample.lat && prev.lng === sample.lng)) {
                    acceptableReadings.push(sample);
                    acceptableReadings.sort((a, b) => a.acc - b.acc);
                    if (acceptableReadings.length > maxSamples) {
                        acceptableReadings.pop();
                    }
                }

                const estimate = fuseCluster(acceptableReadings, { maxSpreadMeters });
                if (onSample) {
                    onSample({
                        latest: sample,
                        estimate,
                        bestRawAccuracy,
                        sampleCount: acceptableReadings.length,
                    });
                }

                if (singleImmediateAccuracy > 0 && sample.acc <= singleImmediateAccuracy) {
                    finish({
                        ok: true,
                        fix: estimate,
                        bestRawAccuracy,
                        sampleCount: estimate.sampleCount,
                        reason: 'immediate',
                        error: null,
                    });
                    return;
                }

                if (estimate.clustered && estimate.sampleCount >= minClusterSamples && estimate.acc <= targetAccuracy) {
                    finish({
                        ok: true,
                        fix: estimate,
                        bestRawAccuracy,
                        sampleCount: estimate.sampleCount,
                        reason: 'clustered',
                        error: null,
                    });
                    return;
                }

                if (settleTimer === null && settleMs > 0) {
                    settleTimer = setTimeout(() => {
                        finish(finalizeFromReadings(false));
                    }, settleMs);
                }
            };

            const handleError = (err) => {
                lastError = err || null;
                if (err && err.code === 1) {
                    finish({
                        ok: false,
                        fix: null,
                        bestRawAccuracy: Number.isFinite(bestRawAccuracy) ? bestRawAccuracy : null,
                        sampleCount: acceptableReadings.length,
                        reason: 'permission_denied',
                        error: err,
                    });
                }
            };

            timeoutTimer = setTimeout(() => {
                finish(finalizeFromReadings(true));
            }, timeoutMs);

            const geoOptions = { enableHighAccuracy: true, timeout: timeoutMs, maximumAge: 0 };
            try {
                if (typeof geo.watchPosition === 'function') {
                    watchId = geo.watchPosition(handlePosition, handleError, geoOptions);
                } else {
                    geo.getCurrentPosition(
                        pos => {
                            handlePosition(pos);
                            if (!finished) finish(finalizeFromReadings(false));
                        },
                        err => {
                            handleError(err);
                            if (!finished) {
                                finish({
                                    ok: false,
                                    fix: null,
                                    bestRawAccuracy: Number.isFinite(bestRawAccuracy) ? bestRawAccuracy : null,
                                    sampleCount: 0,
                                    reason: err && err.code === 1 ? 'permission_denied' : 'unavailable',
                                    error: err || null,
                                });
                            }
                        },
                        geoOptions
                    );
                }
            } catch (err) {
                lastError = err;
                finish({
                    ok: false,
                    fix: null,
                    bestRawAccuracy: null,
                    sampleCount: 0,
                    reason: 'unavailable',
                    error: err,
                });
            }
        });
    }

    return {
        normalizeCoordinates,
        distanceMeters,
        normalizeSample,
        isValidSample,
        fuseCluster,
        shouldReplaceContinuousFix,
        mergeContinuousSamples,
        capturePosition,
    };
});
