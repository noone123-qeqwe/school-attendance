# Indoor location verification

The QR attendance flow uses the device's browser location. Phones may use GPS,
Wi-Fi, and other system location sources, but the web app cannot select a source
or prove that a reported position is genuine. A QR scan, bound device, and
biometric check remain separate requirements where configured.

## What the checks do

- The student scanner and teacher session setup wait briefly for a second fresh
  fix. When two fixes agree, the more accurate reading is used. When fresh
  readings disagree substantially, the app asks for another check rather than
  selecting a favorable outlier.
- A single valid fix is still usable after the refinement window, so devices
  that publish only one fix are not blocked solely for that reason.
- The server rejects reported fixes older than 15 seconds or over 5 seconds in
  the future when the client sends a timestamp. Older clients without this
  field remain compatible. Client timestamps are not tamper proof.
- Accuracy above 50 meters remains insufficient for classroom check-in.
  Reported accuracy never increases the configured geofence radius.
- A fix just outside the radius, within at most 10 meters and half its stated
  accuracy, returns `location_uncertain`. It does **not** record attendance.
  A clearly outside fix returns `outside_classroom`.
- Existing impossible travel, device binding, rotating QR, and identity checks
  continue to run before an attendance record is accepted.

## When a student is physically inside but GPS stays uncertain

Enable Wi-Fi and precise location, wait briefly for the location indicator to
settle, and retry near a window if possible. The instructor can check the
session's laptop location and recalibrate it with a fresh stable fix. If indoor
signals remain poor, use an approved instructor supervised attendance process;
do not enlarge a classroom radius solely to overcome a bad GPS reading.

Browser location cannot reliably establish which room a person occupies or
defeat a device that spoofs coordinates. Classroom level assurance requires an
additional trusted signal or supervised verification, with its own privacy and
security review.
