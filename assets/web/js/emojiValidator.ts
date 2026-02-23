(function (window: Window) {
    const emojiRegex: RegExp = /[\p{So}\p{Cs}\u{1F300}-\u{1FAFF}]/u;

    /**
     * Global emoji validator
     */
    function hasNoEmoji(value?: string | null): boolean {
        if (!value) return true;
        return !emojiRegex.test(value);
    }

    (window as any).hasNoEmoji = hasNoEmoji;

})(window);

export const hasNoEmoji = (value?: string | null): boolean => {
    if (!value) return true;
    const emojiRegex = /[\p{So}\p{Cs}\u{1F300}-\u{1FAFF}]/u;
    return !emojiRegex.test(value);
};
