// Minimale, veilige markdown→HTML. Geen externe afhankelijkheid; HTML wordt eerst
// ge-escaped zodat er geen XSS mogelijk is, daarna passen we een paar regels toe.

function escapeHtml(input: string): string {
    return input
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

export function renderMarkdown(source: string): string {
    const escaped = escapeHtml(source);
    const lines = escaped.split(/\r?\n/);
    const html: string[] = [];
    let inList = false;

    const inline = (text: string): string =>
        text
            .replace(/`([^`]+)`/g, '<code>$1</code>')
            .replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>')
            .replace(/\*([^*]+)\*/g, '<em>$1</em>')
            // Alleen http(s)-links, target _blank + rel voor veiligheid.
            .replace(
                /\[([^\]]+)\]\((https?:\/\/[^\s)]+)\)/g,
                '<a href="$2" target="_blank" rel="noopener noreferrer">$1</a>',
            );

    const closeList = () => {
        if (inList) {
            html.push('</ul>');
            inList = false;
        }
    };

    for (const line of lines) {
        const heading = line.match(/^(#{1,3})\s+(.*)$/);
        const listItem = line.match(/^\s*[-*]\s+(.*)$/);

        if (heading) {
            closeList();
            const level = heading[1].length;
            html.push(`<h${level}>${inline(heading[2])}</h${level}>`);
        } else if (listItem) {
            if (!inList) {
                html.push('<ul>');
                inList = true;
            }
            html.push(`<li>${inline(listItem[1])}</li>`);
        } else if (line.trim() === '') {
            closeList();
        } else {
            closeList();
            html.push(`<p>${inline(line)}</p>`);
        }
    }
    closeList();

    return html.join('');
}
