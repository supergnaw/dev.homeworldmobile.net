/*
	Markdown Editor

        capture keyboard shortcuts:
        https://stackoverflow.com/questions/3680919/overriding-browsers-keyboard-shortcuts

        paste clipboard with rich text to markdown
        https://github.com/euangoddard/clipboard2markdown

        other notes:
        - url-encode links when being addded

*/

function md_initialize(element_id) {
    let md_editor = document.getElementById(element_id);
    ['input', 'keydown', 'keyup', 'click'].forEach( event =>
        md_editor.addEventListener(event, function () {
            md_update_gui_button_highlights(md_editor);
        })
    );
}

function md_update_gui_button_highlights(md_editor) {
    const cursor_start = md_editor.selectionStart;
    const cursor_end = md_editor.selectionEnd;

    // todo: check if this selection contains a new line before automatically returning
    // if (cursor_start != cursor_end) { return; }
    // or apparently just completely remove it because it works just fine without it?

    const content_before = md_editor.value.substring(0, md_editor.selectionStart);
    const content_after = md_editor.value.substring(md_editor.selectionEnd);
    const line_before = content_before.split(/\r?\n/).slice(-1)[0];
    const line_after = content_after.split(/\r?\n/)[0];
    const this_line = (line_before + line_after).trim();

    // is_bold_legacy(line_before, line_after);
    // is_italic(content_before, content_after);
    // is_strikethrough(content_before, content_after);
    detect_formatting(line_before, line_after);

    // font styles
    if ((content_before.match(/\*{2}/g) || []).length % 2 && (content_after.match(/\*{2}/g) || []).length % 2) {
        document.getElementById('md-bold').classList.add('active');
    } else {
        document.getElementById('md-bold').classList.remove('active');
    }

    if ((content_before.match(/\*/g) || []).length % 2 && (content_after.match(/\*/g) || []).length % 2) {
        document.getElementById('md-italic').classList.add('active');
    } else {
        document.getElementById('md-italic').classList.remove('active');
    }

    if ((content_before.match(/~~/g) || []).length % 2 && (content_after.match(/~~/g) || []).length % 2) {
        document.getElementById('md-strikethrough').classList.add('active');
    } else {
        document.getElementById('md-strikethrough').classList.remove('active');
    }

    // block elements
    if (/\d+\.\s+.*$/.test(this_line.trim())) {
        document.getElementById('md-numberlist').classList.add('active');
        md_dent_enable();
    } else {
        document.getElementById('md-numberlist').classList.remove('active');
        md_dent_disable();
    }

    if (/\-\s+.*$/.test(this_line.trim())) {
        document.getElementById('md-bulletlist').classList.add('active');
        md_dent_enable();
    } else {
        document.getElementById('md-bulletlist').classList.remove('active');
        md_dent_disable();
    }

    if (/>\s+.*$/.test(this_line.trim())) {
        document.getElementById('md-quote').classList.add('active');
        md_dent_enable();
    } else {
        document.getElementById('md-quote').classList.remove('active');
        md_dent_disable();
    }

    if (/#\s+.*$/.test(this_line.trim())) {
        document.getElementById('md-header').classList.add('active');
        md_dent_enable();
    } else {
        document.getElementById('md-header').classList.remove('active');
        md_dent_disable();
    }

    if (/!?\[[^\]]*\]\([^\)]*\)/.test(this_line)) {
        let link_start = line_before.lastIndexOf('[') -1;
        if (0 < link_start && '!' == line_before.substring(link_start - 1)) {
            link_start--;
        }
        const link_end = line_after.indexOf(')');
        const link_string = this_line.substring(link_start, link_end + line_before.length);
        if (0 <= link_start && 0 <= link_end) {
            if (/^\[[^\]]*\]\([^\)]*\)$/.test(link_string)) {
                document.getElementById('md-link').classList.add('active');
            } else {
                document.getElementById('md-link').classList.remove('active');
            }
        } else {
            document.getElementById('md-link').classList.remove('active');
        }
    } else {
        document.getElementById('md-link').classList.remove('active');
    }
}

function detect_formatting(textBefore, textAfter) {
    var cursorPosition = textBefore.length;
    var inputText = textBefore + textAfter;

    const patterns = {
        "italic": /\*(?=\S)[^\*]+(?<=\S)\*/g,
        "bold": /\*\*(?=\S)[^\*]+(?<=\S)\*\*/g,
        "both": /\*\*\*(?=\S)[^\*]+(?<=\S)\*\*\*/g,
        "strike": /~~(?=\S)[^~]+(?<=\S)~~/g
    }
    const italicRegex = /\*(?=\S)(?<!\\)[^\*]+(?<=\S)\*/g;
    const boldRegex = /\*\*(?=\S)(?<!\\)[^\*]+(?<=\S)\*\*/g
    const bothRegex = /\*\*\*(?=\S)[^\*]+(?<=\S)\*\*\*/g
    const strikethroughRegex = /~~([^~]+)~~/g

    let formatting = {
        italic: false,
        bold: false,
        strikethrough: false
    }

    // const regexp = /t(e)(st(\d?))/g;
    // const testString = 'test1test2';
    // console.log([...testString.matchAll(regexp)]);
    // return;

    let matches = inputText.matchAll(/\w+/g);
    for (const match of inputText.matchAll(italicRegex)) {
        if (match.index <= cursorPosition && match.index + match[0].length >= cursorPosition) {
            formatting.italic = true;
            break;
        }
    }

    console.log([inputText.slice(0, cursorPosition), '^', inputText.slice(cursorPosition)].join(''));
    console.log(formatting);
}

// selection detection
function is_bold(inputText) {
    inputText = inputText.substring(
        inputText.indexOf("*"),
        inputText.lastIndexOf("*") + 1
    )

    if (!inputText) {
        return false;
    }

    console.log(inputText);

    // textBefore = textBefore.substring(textBefore.indexOf("*"));
    // textAfter = textAfter.substring(0, textAfter.lastIndexOf("*") + 1);
}
function is_bold_legacy(textBefore, textAfter) {
    // check of bold is possible
    textBefore = textBefore.match(/[^\*]*\*\*\w.*$/g);
    textAfter = textAfter.match(/.*\w\*\*/g)

    if (null == textBefore || null == textAfter) {
        return false;
    } else {
        textBefore = textBefore[0];
        textAfter = textAfter[0];
    }

    // remove italics
    textBefore = textBefore.replace(/(\s|^)\*\w[^\*]*($|\w\*(\s|$))/, '');
    textAfter = textAfter.replace(/((^|\s)\*\w|^)[^\*]*\w\*(\s|$)/, '');

    // remove closed-out bold tags
    var exp = /\*\*\w[^\*]*\*\*/;
    textBefore.replace(exp, '');
    textAfter.replace(exp, '');

    console.log(textBefore + "^" + textAfter);

    var countBefore = (textBefore.match(/\*\*/g) || []).length;
    var countAfter = (textAfter.match(/\*\*/g) || []).length;
    var countTotal = countBefore + countAfter;

    if ([countBefore, countAfter].some(num => num < 1)) {
        console.log(false);
        return false;
    }

    textBefore = textBefore.substring(textBefore.indexOf("*"));
    textAfter = textAfter.substring(0, textAfter.lastIndexOf("*") + 1);

    // console.log(textAfter.replace(/\*[^\*]+\*$/, ''))

    var textFull = textBefore + textAfter;
    var cursorPosition = textBefore.length;

    // console.log(textBefore, textAfter);

    if (textBefore.endsWith("*") && textAfter.startsWith("*")) {
        // cursor is adjacent to asterisks
    } else {
        // cursor is not adjacent to asterisks
    }

    var first = textBefore.indexOf("*");
    var last = textAfter.lastIndexOf("*");

    return true;
}

function is_italic(textBefore, textAfter) {
    // console.log(textBefore, textAfter);
    return true;
}

function is_strikethrough(textBefore, textAfter) {
    // console.log(textBefore, textAfter);
    return true;
}

function is_header() {

}

function is_quote() {

}

function is_list() {

}

function is_numbered() {

}

function is_link() {

}

function is_image() {

}

function is_table() {

}

// button highlight toggles
function button_active(elementId) {
    document.getElementById(elementId).classList.add('active');
}

function button_inactive(elementId) {
    document.getElementById(elementId).classList.remove('active');
}

function md_dent_enable() {
    document.getElementById('md-indent').classList.remove('disabled');
    document.getElementById('md-dedent').classList.remove('disabled');
}

function md_dent_disable() {
    document.getElementById('md-indent').classList.add('disabled');
    document.getElementById('md-dedent').classList.add('disabled');
}

function md_button_action(event) {
    // get all the cursor selection data
    const action = event.getAttribute('data-action');
    const text_area = document.getElementById(event.getAttribute('data-target'));
    const cursor_start = text_area.selectionStart;
    const cursor_end = text_area.selectionEnd;
    const content_before = text_area.value.substring(0, text_area.selectionStart);
    const content_selected = (cursor_start < cursor_end) ? text_area.value.substring(cursor_start, cursor_end) : '';
    const content_after = text_area.value.substring(text_area.selectionEnd)
    const start_nl_index = content_before.lastIndexOf('\\n');
    const end_nl_index = content_after.indexOf('\\n');
    const applicable_lines = '\\n' + ((content_before.substring(start_nl_index) + content_selected + content_after.substring(0, end_nl_index)).trim());
    let selection_start_offset = 0;
    let selection_end_offset = 0;

    if ('header' == action) {
        let altered_lines = applicable_lines.replaceAll('\\n', '\\n# ').replaceAll('# #', '##').replaceAll('\\n####### ', '\\n');
        let output = content_before.substring(0, start_nl_index + 1) + altered_lines.trim() + content_after.substring(end_nl_index);
        let line_len_diff = altered_lines.length - applicable_lines.length;
        text_area.value = output;
        if (0 < line_len_diff) {
            selection_start_offset += ('# ' == altered_lines.substring(1, 2)) ? 2 : 1;
            selection_end_offset += line_len_diff * (altered_lines.split('\\n').length - 1);
        } else {
            selection_start_offset -= 7;
            selection_end_offset -= 7 * (altered_lines.split('\\n').length - 1);
        }
    }
    if ('quote' == action) {
        let altered_lines = applicable_lines.replaceAll('\\n', '\\n> ').replaceAll('\\n> > ', '\\n');
        let output = content_before.substring(0, start_nl_index + 1) + altered_lines.trim() + content_after.substring(end_nl_index);
        let line_len_diff = altered_lines.length - applicable_lines.length;
        text_area.value = output;
        if (0 < line_len_diff) {
            selection_start_offset += 2;
            selection_end_offset += line_len_diff;
        } else {
            selection_start_offset -= 2;
            selection_end_offset += line_len_diff;
        }
    }

    text_area.focus();
    text_area.setSelectionRange(cursor_start + selection_start_offset, cursor_end + selection_end_offset);

    md_update_gui_button_highlights();
}