export function HighlightText({ text, highlight, underlineClassName = 'underline decoration-current/30 underline-offset-2' }) {
    if (!highlight || !text.includes(highlight)) {
        return text;
    }

    const [before, after] = text.split(highlight);

    return (
        <>
            {before}
            <span className={underlineClassName}>{highlight}</span>
            {after}
        </>
    );
}
