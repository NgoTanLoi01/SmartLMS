import tinymce from 'tinymce/tinymce';
import 'tinymce/icons/default';
import 'tinymce/themes/silver';
import 'tinymce/models/dom';
import 'tinymce/plugins/advlist';
import 'tinymce/plugins/anchor';
import 'tinymce/plugins/autolink';
import 'tinymce/plugins/autoresize';
import 'tinymce/plugins/charmap';
import 'tinymce/plugins/code';
import 'tinymce/plugins/fullscreen';
import 'tinymce/plugins/help';
import 'tinymce/plugins/image';
import 'tinymce/plugins/insertdatetime';
import 'tinymce/plugins/link';
import 'tinymce/plugins/lists';
import 'tinymce/plugins/media';
import 'tinymce/plugins/preview';
import 'tinymce/plugins/searchreplace';
import 'tinymce/plugins/table';
import 'tinymce/plugins/visualblocks';
import 'tinymce/plugins/wordcount';
import 'tinymce/skins/ui/oxide/skin.min.css';
import oxideContentCss from 'tinymce/skins/ui/oxide/content.min.css?inline';
import defaultContentCss from 'tinymce/skins/content/default/content.min.css?inline';

const baseContentStyle = `${oxideContentCss}\n${defaultContentCss}`;

window.tinymce = tinymce;
window.SmartLmsTinyMce = {
    init(options) {
        if (typeof options.selector !== 'string' || options.selector.trim() === '') {
            return Promise.resolve([]);
        }

        const targets = Array.from(document.querySelectorAll(options.selector))
            .filter((target) => !tinymce.get(target.id) && target.dataset.smartLmsEditorPending !== 'true');

        if (targets.length === 0) {
            return Promise.resolve([]);
        }

        return Promise.all(targets.map((target) => {
            target.dataset.smartLmsEditorPending = 'true';

            return tinymce.init({
                license_key: 'gpl',
                skin: false,
                content_css: false,
                ...options,
                selector: undefined,
                target,
                content_style: `${baseContentStyle}\n${options.content_style ?? ''}`,
            }).finally(() => {
                delete target.dataset.smartLmsEditorPending;
            });
        })).then((editors) => editors.flat());
    },
};
