const test = require('node:test');
const assert = require('node:assert/strict');
const GpsSampler = require('../../public/js/gps-sampler.js');

test('fuseCluster computes inverse-variance weighted centroid and excludes multipath outliers', () => {
    const now = Date.now();
    // Three clustered fixes near (14.50000, 121.00000) + one 90m multipath outlier
    const cluster = GpsSampler.fuseCluster([
        { lat: 14.50000, lng: 121.00000, acc: 10, timestamp: now - 2000 },
        { lat: 14.50004, lng: 121.00004, acc: 20, timestamp: now - 1000 },
        { lat: 14.50002, lng: 121.00002, acc: 10, timestamp: now },
        { lat: 14.50085, lng: 121.00085, acc: 15, timestamp: now - 500 },
    ]);

    assert.equal(cluster.clustered, true);
    assert.equal(cluster.consistent, true);
    assert.equal(cluster.sampleCount, 3);
    assert.equal(cluster.timestamp, now);
    // Weighted average pulls slightly closer to the two higher-precision 10m samples than the 20m sample
    assert.ok(Math.abs(cluster.lat - 14.500013) < 0.00001);
    assert.ok(cluster.acc >= 10, 'fusion must not claim better accuracy than its best sensor sample');
});

test('mergeContinuousSamples refuses to replace an active precise lock with a coarse fix', () => {
    const t0 = Date.now();
    let state = GpsSampler.mergeContinuousSamples([], {
        coords: { latitude: 14.5001, longitude: 121.0001, accuracy: 9 },
        timestamp: t0,
    });
    assert.equal(state.estimate.acc, 9);

    // 7 seconds later, a coarse 350m network/IP reading arrives
    const degraded = GpsSampler.mergeContinuousSamples(state.buffer, {
        coords: { latitude: 14.5030, longitude: 121.0030, accuracy: 350 },
        timestamp: t0 + 7000,
    });
    assert.equal(degraded.accepted, false);
    assert.equal(degraded.estimate.acc, 9);
    assert.equal(GpsSampler.shouldReplaceContinuousFix(state.estimate, {
        lat: 14.5030, lng: 121.0030, acc: 350, timestamp: t0 + 7000,
    }), false);
});

test('mergeContinuousSamples rejects stale and out-of-order cached fixes', () => {
    const now = Date.now();
    const oldFix = { lat: 14.5001, lng: 121.0001, acc: 4, timestamp: now - 60000 };
    const freshFix = { lat: 14.5002, lng: 121.0002, acc: 12, timestamp: now };
    const initial = GpsSampler.mergeContinuousSamples([], freshFix, { nowMs: now });
    const stale = GpsSampler.mergeContinuousSamples(initial.buffer, oldFix, { nowMs: now });
    assert.equal(stale.accepted, false);
    assert.equal(stale.estimate.timestamp, now);
    assert.equal(GpsSampler.shouldReplaceContinuousFix(freshFix, oldFix, { nowMs: now }), false);
});

test('capturePosition preserves single valid fix on timeout instead of dropping it', async () => {
    const now = Date.now();
    const mockGeo = {
        watchPosition(onSuccess) {
            setTimeout(() => {
                onSuccess({
                    coords: { latitude: 14.5002, longitude: 121.0002, accuracy: 22 },
                    timestamp: now,
                });
            }, 5);
            return 1;
        },
        clearWatch() {},
    };

    const result = await GpsSampler.capturePosition({
        geolocation: mockGeo,
        timeoutMs: 80,
        settleMs: 30,
        targetAccuracy: 12,
        maxAcceptableAccuracy: 50,
        allowSingleFallback: true,
    });

    assert.equal(result.ok, true);
    assert.equal(result.sampleCount, 1);
    assert.equal(result.fix.acc, 22);
    assert.equal(result.fix.toGeolocationPosition().coords.latitude, 14.5002);
});

test('capturePosition does not accept a lone fix when stable samples are required', async () => {
    const now = Date.now();
    const mockGeo = {
        watchPosition(onSuccess) {
            setTimeout(() => onSuccess({
                coords: { latitude: 14.5002, longitude: 121.0002, accuracy: 8 },
                timestamp: now,
            }), 5);
            return 7;
        },
        clearWatch() {},
    };

    const result = await GpsSampler.capturePosition({
        geolocation: mockGeo,
        timeoutMs: 80,
        settleMs: 20,
        minClusterSamples: 2,
        allowSingleFallback: false,
    });

    assert.equal(result.ok, false);
    assert.equal(result.reason, 'insufficient_samples');
});

test('capturePosition settles early on clustered fixes and rejects wildly disagreeing fixes when configured', async () => {
    const now = Date.now();
    const clusterGeo = {
        watchPosition(onSuccess) {
            onSuccess({ coords: { latitude: 14.50000, longitude: 121.00000, accuracy: 12 }, timestamp: now - 500 });
            onSuccess({ coords: { latitude: 14.50002, longitude: 121.00002, accuracy: 12 }, timestamp: now });
            return 1;
        },
        clearWatch() {},
    };
    const fastCluster = await GpsSampler.capturePosition({
        geolocation: clusterGeo,
        timeoutMs: 500,
        settleMs: 200,
        targetAccuracy: 15,
        maxAcceptableAccuracy: 50,
    });
    assert.equal(fastCluster.ok, true);
    assert.equal(fastCluster.reason, 'clustered');
    assert.equal(fastCluster.sampleCount, 2);

    const unstableGeo = {
        watchPosition(onSuccess) {
            onSuccess({ coords: { latitude: 14.5000, longitude: 121.0000, accuracy: 10 }, timestamp: now - 500 });
            onSuccess({ coords: { latitude: 14.5015, longitude: 121.0015, accuracy: 10 }, timestamp: now });
            return 2;
        },
        clearWatch() {},
    };
    const unstableResult = await GpsSampler.capturePosition({
        geolocation: unstableGeo,
        timeoutMs: 40,
        settleMs: 20,
        requireClusterIfDisagreeing: true,
    });
    assert.equal(unstableResult.ok, false);
    assert.equal(unstableResult.reason, 'unstable');
});

test('capturePosition supports coarse fallback for presence monitoring without triggering denied errors', async () => {
    const now = Date.now();
    const coarseGeo = {
        watchPosition(onSuccess) {
            onSuccess({ coords: { latitude: 14.5001, longitude: 121.0001, accuracy: 140 }, timestamp: now });
            return 3;
        },
        clearWatch() {},
    };
    const result = await GpsSampler.capturePosition({
        geolocation: coarseGeo,
        timeoutMs: 30,
        settleMs: 15,
        fallbackToBestCoarse: true,
    });
    assert.equal(result.ok, true);
    assert.equal(result.reason, 'coarse_fallback');
    assert.equal(result.fix.acc, 140);
});

test('normalizeCoordinates and getCurrentPosition fallback behave consistently', async () => {
    const [lat, lng] = GpsSampler.normalizeCoordinates(123.619, 12.371);
    assert.ok(Math.abs(lat - 12.371) < 0.0001);
    assert.ok(Math.abs(lng - 123.619) < 0.0001);

    const moscow = GpsSampler.normalizeCoordinates(55.7558, 37.6173);
    assert.ok(Math.abs(moscow[0] - 55.7558) < 0.0001);
    assert.ok(Math.abs(moscow[1] - 37.6173) < 0.0001);
    assert.equal(GpsSampler.normalizeCoordinates(91, 181), null);
    assert.equal(GpsSampler.distanceMeters(91, 181, 0, 0), Infinity);
    assert.equal(GpsSampler.normalizeSample({ lat: 14.5, lng: 121, acc: 8 }), null);

    const now = Date.now();
    const legacyGeo = {
        getCurrentPosition(onSuccess) {
            onSuccess({ coords: { latitude: 14.5001, longitude: 121.0001, accuracy: 16 }, timestamp: now });
        },
    };
    const result = await GpsSampler.capturePosition({
        geolocation: legacyGeo,
        timeoutMs: 50,
    });
    assert.equal(result.ok, true);
    assert.equal(result.fix.acc, 16);
});
