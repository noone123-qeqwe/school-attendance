const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const postcss = require('postcss');

const root = postcss.parse(fs.readFileSync(path.join(__dirname, '../../public/css/portal-layout-refinements.css'), 'utf8'));

function declarations(selector) {
    const rules = [];
    root.walkRules(selector, rule => rules.push(rule));
    assert.equal(rules.length, 1, `Expected one gutter owner for ${selector}`);
    const rule = rules[0];
    assert.equal(rule.parent.name, 'media');
    assert.equal(rule.parent.params, 'screen and (max-width: 768px)', 'Phone rules must not alter desktop or printed reports');
    return Object.fromEntries(rule.nodes.filter(node => node.type === 'decl').map(node => [node.prop, node]));
}

test('the page keeps equal 16px gutters after device safe areas, even when legacy page styles reset padding', () => {
    const shell = declarations('body #mainContent.main-content');
    const page = declarations('body #mainContent.main-content > .page-enter');
    assert.match(shell['padding-left'].value, /--sal.*safe-area-inset-left/);
    assert.match(shell['padding-right'].value, /--sar.*safe-area-inset-right/);
    for (const side of ['left', 'right']) {
        assert.equal(page['padding-' + side].value, '16px');
        assert.equal(page['padding-' + side].important, true);
    }
    assert.equal(page['box-sizing'].value, 'border-box');
    assert.equal(page['min-width'].value, '0');
    // Check the resulting available reading width at the phone/tablet boundary,
    // including a landscape cutout. A safe-area inset is applied only once.
    for (const [width, leftInset, rightInset] of [[320, 0, 0], [390, 0, 0], [768, 44, 0]]) {
        const left = leftInset + parseFloat(page['padding-left'].value);
        const right = rightInset + parseFloat(page['padding-right'].value);
        assert.equal(left - leftInset, 16);
        assert.equal(right - rightInset, 16);
        assert.ok(width - left - right >= 288);
    }
});
