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
    console.log('Initializing markdown editor');
    let md_editor = document.getElementById(element_id);
    ['input', 'keydown', 'keyup', 'click'].forEach( event =>
        md_editor.addEventListener(event, function () {
            md_update_gui_buttons(md_editor);
        })
    );
}

function md_update_gui_buttons(element) {
    const cursor_start = element.selectionStart;
    const cursor_end = element.selectionEnd;
    if (cursor_start != cursor_end) { return; }
    const content_before = element.value.substring(0, element.selectionStart);
    const content_after = element.value.substring(element.selectionEnd);
    const line_before = content_before.substring(content_before.lastIndexOf('\\n'));
    const line_after = content_after.substring(0, content_after.indexOf('\\n'));
    const this_line = (line_before + line_after).trim();

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
        console.log(link_string);
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
        console.log(line_len_diff);
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
        console.log(line_len_diff);
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

    md_update_gui_buttons();
}