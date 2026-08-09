@push('scripts')
    @if ($isCourseManager)
        @vite('resources/js/pages/course-editors.js')
    @endif
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
    @if ($isCourseManager)
        @include('courses.partials.scripts.editor')
    @endif
    @include('courses.partials.scripts.interactions')
@endpush
