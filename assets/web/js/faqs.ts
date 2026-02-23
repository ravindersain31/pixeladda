window.addEventListener('DOMContentLoaded', () => {
    const searchInput = $('#faqSearch'); 
    const faqSections = $('.faq-section'); 
    const noResult = $('#noResult'); 
    const clearBtn = $('#clearSearch');


    function escapeRegex(str: string): string {
        return str.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
    }

    function highlightText($el: JQuery<HTMLElement>, regex: RegExp): void {
        if (!$el.data('original')) {
            $el.data('original', $el.html());
        } else {
            $el.html($el.data('original') as string); 
        }

        $el.contents().each((_, node) => {
            if (node.nodeType === Node.TEXT_NODE) { 
                const text = node.nodeValue ?? '';
                const replaced = text.replace(regex, '<span class="faq-highlight-word">$1</span>');
                if (replaced !== text) {
                    $(node).replaceWith(replaced);
                }
            } else if (node.nodeType === Node.ELEMENT_NODE) { 
                highlightText($(node as HTMLElement), regex); 
            }
        });
    }

    searchInput.on('keyup', () => {
        const query = (searchInput.val() as string).toLowerCase().trim();
        const words = query.split(/\s+/).filter(Boolean);
        let foundAny = false;

        faqSections.each(function () {
            const $section = $(this);
            const $items = $section.find('.accordion-item');
            let sectionHasMatch = false;

            $items.each(function () {
                const $item = $(this);
                const $questionEl = $item.find('.accordion-button');
                const $answerEl = $item.find('.accordion-body');
                const keywords = ($item.data('keywords') || '').toString().toLowerCase();

                // Reset to original content
                if (!$questionEl.data('original')) {
                    $questionEl.data('original', $questionEl.html());
                }
                if (!$answerEl.data('original')) {
                    $answerEl.data('original', $answerEl.html());
                }
                $questionEl.html($questionEl.data('original') as string);
                $answerEl.html($answerEl.data('original') as string);

                let isMatch = false;

                if (query.length > 0) {
                    let regex: RegExp;
                    if (words.length > 1 && query.includes(" ")) {
                        // Phrase match
                        regex = new RegExp("(" + escapeRegex(query) + ")", "gi");
                    } else {
                        // Match any word
                        const wordPatterns = words.map(escapeRegex);
                        regex = new RegExp("(" + wordPatterns.join("|") + ")", "gi");
                    }

                    const questionText = $questionEl.text().toLowerCase();
                    const answerText = $answerEl.text().toLowerCase();

                    if (regex.test(questionText) || 
                        regex.test(answerText) || 
                        (keywords && regex.test(keywords))
                    ) {
                        isMatch = true;
                        highlightText($questionEl, regex);
                        highlightText($answerEl, regex);
                    }
                }

                if (isMatch || words.length === 0) {
                    $item.show();
                    sectionHasMatch = true;
                    foundAny = true;
                } else {
                    $item.hide();
                }
            });

            $section.toggle(sectionHasMatch);
        });

        if (!foundAny && words.length > 0) {
            noResult.fadeIn(200);
        } else {
            noResult.fadeOut(200);
        }
    });

    function toggleClearButton(): void {
        if ((searchInput.val() as string).trim() !== '') {
            clearBtn.removeClass('d-none');
        } else {
            clearBtn.addClass('d-none');
        }
    }

    searchInput.on('input', toggleClearButton);

    clearBtn.on('click', () => {
        searchInput.val('');
        toggleClearButton();
        searchInput.trigger('keyup');
        searchInput.focus();
    });

    $(document).on('click', '.accordion-button', function () {
        $(this).closest('.accordion')!
            .find('.collapse')
            .not($(this).attr('data-bs-target') as string)
            .collapse('hide');
    });
});
