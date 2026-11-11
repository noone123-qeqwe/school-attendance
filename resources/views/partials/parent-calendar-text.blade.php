// Names, remarks and event titles are data, including when they contain HTML characters.
function parentEscapeHtml(value) {
    const entities = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' };
    return String(value ?? '').replace(/[&<>"']/g, character => entities[character]);
}
function parentCalendarColor(value) {
    return /^#[0-9a-f]{6}$/i.test(String(value)) ? value : '#8b5cf6';
}
