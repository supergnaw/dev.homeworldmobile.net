/*
	Markdown Editor
*/

function md_initialize(element_id) {
    let md_editor = document.getElementById(element_id);
    ['input', 'keydown', 'keyup'].forEach( event =>
        md_editor.addEventListener(event, function () {
            md_update_gui_buttons();
        })
    );
}

function md_update_gui_buttons() {
    const text_area = document.getElementById('page_content');
    const cursor_start = text_area.selectionStart;
    const cursor_end = text_area.selectionEnd;
    if (cursor_start != cursor_end) { return; }
    const content_before = text_area.value.substring(0, text_area.selectionStart);
    const content_after = text_area.value.substring(text_area.selectionEnd);
    const line_before = content_before.substring(content_before.lastIndexOf('\\n'));
    const line_after = content_after.substring(0, content_after.indexOf('\\n'));
    const this_line = (line_before + line_after).trim();

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

    if (/\d+\.\s+.*$/.test(this_line.trim())) {
        document.getElementById('md-numberlist').classList.add('active');
    } else {
        document.getElementById('md-numberlist').classList.remove('active');
    }

    if (/\-\s+.*$/.test(this_line.trim())) {
        document.getElementById('md-bulletlist').classList.add('active');
    } else {
        document.getElementById('md-bulletlist').classList.remove('active');
    }

    if (/>\s+.*$/.test(this_line.trim())) {
        document.getElementById('md-quote').classList.add('active');
    } else {
        document.getElementById('md-quote').classList.remove('active');
    }

    if (/#\s+.*$/.test(this_line.trim())) {
        document.getElementById('md-header').classList.add('active');
    } else {
        document.getElementById('md-header').classList.remove('active');
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