    <script>
        // Fix lỗi Bootstrap 5 chặn focus (không cho gõ chữ) vào các popup bên trong TinyMCE (như popup chèn link)
        document.addEventListener('focusin', (e) => {
            if (e.target.closest(".tox-tinymce-aux, .moxman-window, .tam-assetmanager-root") !== null) {
                e.stopImmediatePropagation();
            }
        });

        const lessonContentBlocks = [
            {
                text: 'Khối ghi nhớ',
                content: '<div class="lesson-callout lesson-callout--note"><strong>Ghi nhớ</strong><p>Nhập ý chính học sinh cần nắm ở đây.</p></div><p></p>'
            },
            {
                text: 'Khối ví dụ',
                content: '<div class="lesson-callout lesson-callout--example"><strong>Ví dụ minh họa</strong><p>Thêm ví dụ cụ thể để học sinh dễ hình dung.</p></div><p></p>'
            },
            {
                text: 'Khối lưu ý',
                content: '<div class="lesson-callout lesson-callout--warning"><strong>Lưu ý</strong><p>Nhập cảnh báo, lỗi thường gặp hoặc điểm cần chú ý.</p></div><p></p>'
            },
            {
                text: 'Bài thực hành',
                content: '<div class="lesson-callout lesson-callout--practice"><strong>Bài thực hành</strong><ol><li>Bước 1: mô tả việc cần làm.</li><li>Bước 2: yêu cầu học sinh thực hiện.</li></ol></div><p></p>'
            },
            {
                text: 'Checklist',
                content: '<ul class="lesson-checklist"><li>Hoàn thành mục thứ nhất.</li><li>Kiểm tra lại kết quả.</li><li>Gửi bài hoặc đánh dấu hoàn thành.</li></ul><p></p>'
            },
            {
                text: 'Câu hỏi tự kiểm tra',
                content: '<div class="lesson-self-check"><h4>Câu hỏi tự kiểm tra</h4><ol><li>Em hiểu nội dung chính của bài này là gì?</li><li>Hãy nêu một ví dụ áp dụng.</li></ol></div><p></p>'
            }
        ];

        const lessonEditorContentStyle = `
            body {
                color: #202634;
                font-family: "Be Vietnam Pro", sans-serif;
                font-size: 15px;
                line-height: 1.75;
                padding: 10px 14px;
            }
            h2, h3, h4 { color: #111827; line-height: 1.35; }
            table { border-collapse: collapse; width: 100%; }
            table td, table th { border: 1px solid #dbe3ef; padding: 10px; }
            pre {
                background: #111827;
                border-radius: 12px;
                color: #e5e7eb;
                overflow-x: auto;
                padding: 14px;
            }
            .lesson-callout,
            .lesson-self-check {
                border: 1px solid #dbeafe;
                border-radius: 16px;
                margin: 16px 0;
                padding: 14px 16px;
            }
            .lesson-callout strong,
            .lesson-self-check h4 {
                display: block;
                font-size: 15px;
                margin: 0 0 6px;
            }
            .lesson-callout p:last-child,
            .lesson-self-check ol:last-child { margin-bottom: 0; }
            .lesson-callout--note { background: #eff6ff; border-color: #bfdbfe; }
            .lesson-callout--example { background: #ecfdf5; border-color: #bbf7d0; }
            .lesson-callout--warning { background: #fff7ed; border-color: #fed7aa; }
            .lesson-callout--practice { background: #f5f3ff; border-color: #ddd6fe; }
            .lesson-checklist {
                background: #f8fafc;
                border: 1px solid #e2e8f0;
                border-radius: 16px;
                list-style: none;
                margin: 16px 0;
                padding: 14px 16px;
            }
            .lesson-checklist li {
                margin: 8px 0;
                padding-left: 28px;
                position: relative;
            }
            .lesson-checklist li::before {
                background: #16a34a;
                border-radius: 50%;
                content: "";
                height: 7px;
                left: 0;
                position: absolute;
                top: .7em;
                width: 7px;
            }
            .lesson-self-check { background: #f8fafc; border-color: #c7d2fe; }
        `;

        // Khởi tạo trình soạn thảo bài học
        tinymce.init({
            selector: '#addLessonContent, #editLessonContent',
            height: 420,
            min_height: 320,
            menubar: false,
            branding: false,
            promotion: false,
            resize: true,
            plugins: 'lists link image preview searchreplace visualblocks code fullscreen table wordcount autoresize',
            toolbar: 'undo redo | blocks | bold italic underline forecolor backcolor | lessonblocks | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link table image | removeformat | preview fullscreen code',
            toolbar_mode: 'sliding',
            block_formats: 'Đoạn văn=p; Tiêu đề lớn=h2; Tiêu đề vừa=h3; Tiêu đề nhỏ=h4; Trích dẫn=blockquote; Mã lệnh=pre',
            style_formats: [
                { title: 'Đoạn nhấn mạnh', block: 'p', classes: 'lesson-lead-text' },
                { title: 'Ghi nhớ', block: 'div', classes: 'lesson-callout lesson-callout--note', wrapper: true },
                { title: 'Ví dụ minh họa', block: 'div', classes: 'lesson-callout lesson-callout--example', wrapper: true },
                { title: 'Lưu ý', block: 'div', classes: 'lesson-callout lesson-callout--warning', wrapper: true },
                { title: 'Bài thực hành', block: 'div', classes: 'lesson-callout lesson-callout--practice', wrapper: true }
            ],
            content_style: lessonEditorContentStyle,
            paste_data_images: true,
            automatic_uploads: false,
            convert_urls: false,
            extended_valid_elements: 'div[class],section[class],span[class],ul[class],ol[class],li[class],pre[class],code[class],blockquote[class]',
            setup: function(editor) {
                editor.ui.registry.addMenuButton('lessonblocks', {
                    text: 'Khối nội dung',
                    tooltip: 'Chèn nhanh mẫu nội dung bài học',
                    fetch: function(callback) {
                        callback(lessonContentBlocks.map((block) => ({
                            type: 'menuitem',
                            text: block.text,
                            onAction: function() {
                                editor.insertContent(block.content);
                                editor.save();
                            }
                        })));
                    }
                });

                // Đồng bộ dữ liệu từ TinyMCE về textarea gốc để Form có thể gửi đi
                editor.on('change keyup undo redo SetContent', function() {
                    editor.save();
                });
            }
        });
    </script>
