import { memo, useState, useEffect, useCallback } from "@wordpress/element";
import { __ } from "@wordpress/i18n";
import { checkContrast } from "./colorContrast.js";
import "./WcagContrastChecker.scss";
import Icon from "../../utils/Icon.js";

import useFields from "../Fields/FieldsData.js";

const debounce = (fn, delay = 100) => {
    let timer;
    return (...args) => {
        clearTimeout(timer);
        timer = setTimeout(() => fn(...args), delay);
    };
};

const WcagContrastChecker = (props) => {
    const [results, setResults] = useState([]);

    const comparisons = props.fields?.comparisons || [];
    const fontSize = useFields((state) => state.getFieldValue("font_size"));

    const fieldValues = useFields((state) =>
        comparisons.reduce((acc, cmp) => {
            acc[cmp.foreground_field] = state.getFieldValue(cmp.foreground_field);
            acc[cmp.background_field] = state.getFieldValue(cmp.background_field);
            return acc;
        }, {})
    );

    const tooltipText = {
        AAA: __("AAA: Meets enhanced WCAG 2.1 contrast requirements (≥7:1).", "complianz-gdpr"),
        AA: __("AA: Meets minimum WCAG 2.1 contrast requirements (≥4.5:1).", "complianz-gdpr"),
        Fail: __("Fail: Does not meet WCAG 2.1 minimum contrast (below 4.5:1).", "complianz-gdpr"),
    };

    const computePairs = useCallback(() => {
        return comparisons.map((cmp) => {
        const fgRaw = fieldValues[cmp.foreground_field];
        const bgRaw = fieldValues[cmp.background_field];

        const fg =
            fgRaw && typeof fgRaw === "object" && cmp.foreground_key
            ? fgRaw[cmp.foreground_key]
            : fgRaw;

        const bg =
            bgRaw && typeof bgRaw === "object" && cmp.background_key
            ? bgRaw[cmp.background_key]
            : bg;

        return {
            name: `${cmp.foreground_label} vs ${cmp.background_label}`,
            fg,
            bg,
        };
        });
    }, [comparisons, fieldValues]);

    useEffect(() => {
        const run = debounce(() => {
        const pairs = computePairs();
        const calc = pairs.map((pair) => {
            const { ratio, level, pass } = checkContrast(pair.fg, pair.bg, fontSize);
            return { ...pair, ratio, level, pass };
        });
        setResults(calc);
        }, 100);

        run();
    }, [comparisons, fieldValues, fontSize, computePairs]);

    if (!results.length) return null;

    return (
        <div className="cmplz-contrast-checker" aria-live="polite">
        <ul>
            {results.map((res, i) => (
            <li key={i} className="cmplz-contrast-row">
                <span
                className={`cmplz-contrast-value ${
                    res.level === "AAA"
                    ? "cmplz-pass-aaa"
                    : res.level === "AA"
                    ? "cmplz-pass-aa"
                    : "cmplz-fail"
                }`}
                >
                <Icon
                    name="contrast"
                    size={16}
                    color={
                    res.level === "AAA"
                        ? "green"
                        : res.level === "AA"
                        ? "orange"
                        : "red"
                    }
                    tooltip={tooltipText[res.level]}
                />
                <strong>{res.ratio ? res.ratio.toFixed(2) : "—"}</strong>
                <span>{res.level}</span>
                </span>
                <p>- {res.name}</p>
            </li>
            ))}
        </ul>
        </div>
    );
};

export default memo(WcagContrastChecker);