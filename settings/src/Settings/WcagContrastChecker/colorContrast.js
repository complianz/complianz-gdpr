/**
 * Utility functions for calculating WCAG colour contrast.
 * These functions are pure and have no external dependencies.
 */

/**
 * Parse a CSS colour string into an RGB object.
 * Supports: #RRGGBB, #RGB, rgb(r,g,b), rgba(r,g,b,a), hsl(h,s%,l%), hsla(h,s%,l%,a).
 */
export function parseColor(colorStr) {
    if (!colorStr || typeof colorStr !== 'string') return null;
    let color = colorStr.trim().toLowerCase();
  
    // Hex notation
    if (color.startsWith('#')) {
      let hex = color.slice(1);
      if (hex.length === 3) {
        const r = parseInt(hex[0] + hex[0], 16);
        const g = parseInt(hex[1] + hex[1], 16);
        const b = parseInt(hex[2] + hex[2], 16);
        return { r, g, b };
      }
      if (hex.length === 6) {
        const r = parseInt(hex.slice(0, 2), 16);
        const g = parseInt(hex.slice(2, 4), 16);
        const b = parseInt(hex.slice(4, 6), 16);
        return { r, g, b };
      }
      return null;
    }
  
    // rgb() / rgba()
    const rgbMatch = color.match(/^rgba?\(([^)]+)\)$/);
    if (rgbMatch) {
      const parts = rgbMatch[1].split(',').map(p => p.trim());
      if (parts.length >= 3) {
        const r = parseFloat(parts[0]);
        const g = parseFloat(parts[1]);
        const b = parseFloat(parts[2]);
        if (Number.isFinite(r) && Number.isFinite(g) && Number.isFinite(b)) {
          return { r, g, b };
        }
      }
      return null;
    }
  
    // hsl() / hsla()
    const hslMatch = color.match(/^hsla?\(([^)]+)\)$/);
    if (hslMatch) {
      const parts = hslMatch[1].split(',').map(p => p.trim());
      if (parts.length >= 3) {
        const h = parseFloat(parts[0]);
        const s = parseFloat(parts[1]) / 100;
        const l = parseFloat(parts[2]) / 100;
        if (Number.isFinite(h) && Number.isFinite(s) && Number.isFinite(l)) {
          const { r, g, b } = hslToRgb(h, s, l);
          return { r: Math.round(r), g: Math.round(g), b: Math.round(b) };
        }
      }
      return null;
    }
  
    return null;
}
  
/**
 * Convert HSL (h ∈ [0,360], s ∈ [0,1], l ∈ [0,1]) to RGB.
 * Returns r,g,b in [0,255].
 */
function hslToRgb(h, s, l) {
    const c = (1 - Math.abs(2 * l - 1)) * s;
    const hPrime = ((h % 360) / 60);
    const x = c * (1 - Math.abs((hPrime % 2) - 1));
    let r1 = 0, g1 = 0, b1 = 0;

    if (0 <= hPrime && hPrime < 1) { 
        r1 = c; g1 = x; b1 = 0; 
    } else if (1 <= hPrime && hPrime < 2) { 
        r1 = x; g1 = c; b1 = 0; 
    } else if (2 <= hPrime && hPrime < 3) { 
        r1 = 0; g1 = c; b1 = x; 
    } else if (3 <= hPrime && hPrime < 4) { 
        r1 = 0; g1 = x; b1 = c; 
    } else if (4 <= hPrime && hPrime < 5) { 
        r1 = x; g1 = 0; b1 = c; 
    } else if (5 <= hPrime && hPrime < 6) { 
        r1 = c; g1 = 0; b1 = x; 
    }

    const m = l - c / 2;

    return {
        r: (r1 + m) * 255,
        g: (g1 + m) * 255,
        b: (b1 + m) * 255
    };
}

/**
 * Calculate the relative luminance of an RGB colour.
 * Implements the WCAG luminance algorithm
 */
export function luminance(r, g, b) {
    const transform = channel => {
        const v = channel / 255;
        return v <= 0.03928 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4);
    };
    const a = [r, g, b].map(transform);
    return a[0] * 0.2126 + a[1] * 0.7152 + a[2] * 0.0722;
}

/**
 * Compute the contrast ratio between two colours.
 * Returns a number ≥ 1, or null if parsing fails.
 */
export function contrastRatio(colour1, colour2) {
    const c1 = typeof colour1 === 'string' ? parseColor(colour1) : colour1;
    const c2 = typeof colour2 === 'string' ? parseColor(colour2) : colour2;

    if (!c1 || !c2) return null;

    const L1 = luminance(c1.r, c1.g, c1.b);
    const L2 = luminance(c2.r, c2.g, c2.b);
    const lighter = Math.max(L1, L2);
    const darker  = Math.min(L1, L2);

    return (lighter + 0.05) / (darker + 0.05);
}

/**
 * Determine the WCAG compliance level for a ratio and font size.
 * For small text (<18pt): AAA ≥ 7, AA ≥ 4.5; for large text (≥18pt): AAA ≥ 4.5, AA ≥ 3.
 */
export function getComplianceLevel(ratio, fontSize = 12) {
    if (ratio == null) return 'Fail';

    const largeText = fontSize >= 18;

    if (largeText) {
        if (ratio >= 4.5) return 'AAA';
        if (ratio >= 3)   return 'AA';
        return 'Fail';
    }

    // small text
    if (ratio >= 7)   return 'AAA';
    if (ratio >= 4.5) return 'AA';
    return 'Fail';
}

/**
 * Evaluate contrast for a foreground/background pair, returning ratio, level and pass/fail.
 * `fontSize` defaults to 12pt.  Pass is true only when the level is AA or AAA.
 */
export function checkContrast(fgColour, bgColour, fontSize = 12) {
    const ratio = contrastRatio(fgColour, bgColour);
    const level = getComplianceLevel(ratio, fontSize);
    const pass  = level === 'AA' || level === 'AAA';

    return { ratio, level, pass };
}
