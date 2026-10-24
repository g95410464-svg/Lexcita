import test from 'node:test';
import assert from 'node:assert/strict';
import { normalizeRects } from '../../resources/js/sli-library-geometry.js';

test('selection geometry is identical at different zoom levels', () => {
    assert.deepEqual(
        normalizeRects([{left: 50, top: 80, right: 150, bottom: 100}], {left: 0, top: 0, width: 500, height: 1000}),
        normalizeRects([{left: 100, top: 160, right: 300, bottom: 200}], {left: 0, top: 0, width: 1000, height: 2000}),
    );
});

test('clips selection to the PDF page and ignores invisible rectangles', () => {
    assert.deepEqual(normalizeRects([
        {left: 0, top: 0, right: 10, bottom: 10},
        {left: 5, top: 10, right: 30, bottom: 30},
        {left: 90, top: 90, right: 120, bottom: 120},
    ], {left: 10, top: 10, width: 100, height: 100}), [
        {x: 0, y: 0, width: .2, height: .2},
        {x: .8, y: .8, width: .2, height: .2},
    ]);
});
