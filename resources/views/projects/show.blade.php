@extends('layouts.app')
@section('title', $project->name)
@section('content')
<div class="flex flex-wrap items-start justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-semibold flex items-center gap-2">
            <span class="w-3 h-3 rounded-full" style="background: {{ $project->color }}"></span>{{ $project->name }}
        </h1>
        <p class="text-slate-500 text-sm mt-1">{{ $project->description }}</p>
        <p class="text-xs text-slate-400 mt-1">Team: {{ collect($team)->pluck('name')->join(', ') }}</p>
    </div>
    @can('update', $project)
        <div class="flex flex-wrap items-center gap-2">
            <form method="POST" action="{{ route('projects.members.store', $project) }}" class="flex gap-2">
                @csrf
                <input type="email" name="email" placeholder="Invite by email" required class="border rounded px-2 py-1 text-sm">
                <button class="text-sm border rounded px-3 py-1 hover:bg-slate-100">Add</button>
            </form>
            <form method="POST" action="{{ route('projects.destroy', $project) }}" onsubmit="return confirm('Delete this project and all its tasks?')">
                @csrf @method('DELETE')
                <button class="text-sm border border-red-300 text-red-600 rounded px-3 py-1 hover:bg-red-50">Delete</button>
            </form>
        </div>
    @endcan
</div>

<form id="new-task" class="bg-white border rounded-lg p-3 mb-6 flex flex-wrap gap-2 items-center">
    <input name="title" placeholder="New task title" required class="flex-1 min-w-[200px] border rounded px-3 py-1.5 text-sm">
    <select name="priority" class="border rounded px-2 py-1.5 text-sm">
        <option value="low">Low</option><option value="medium" selected>Medium</option><option value="high">High</option>
    </select>
    <input type="date" name="due_date" class="border rounded px-2 py-1.5 text-sm">
    <select name="assignee_id" id="assignee" class="border rounded px-2 py-1.5 text-sm"><option value="">Unassigned</option></select>
    <button class="bg-indigo-600 text-white text-sm rounded px-4 py-1.5 hover:bg-indigo-700">Add task</button>
</form>
<div id="error" class="hidden mb-4 rounded bg-red-50 border border-red-200 text-red-700 px-4 py-2 text-sm"></div>

<div id="board" class="grid md:grid-cols-3 gap-4"></div>

<script>
const PROJECT_ID = @json($project->id);
const TEAM = @json($team);
const CSRF = document.querySelector('meta[name="csrf-token"]').content;
const COLUMNS = [
    { key: 'todo', label: 'To Do' },
    { key: 'in_progress', label: 'In Progress' },
    { key: 'done', label: 'Done' },
];
const PRIORITY_CLASS = { low: 'bg-slate-100 text-slate-600', medium: 'bg-amber-100 text-amber-700', high: 'bg-red-100 text-red-700' };
let tasks = [];

// Escape everything we put into innerHTML (XSS protection).
const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

async function api(url, method = 'GET', body = null) {
    const res = await fetch(url, {
        method,
        headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: body ? JSON.stringify(body) : undefined,
    });
    if (!res.ok) {
        const err = await res.json().catch(() => ({}));
        const first = err.errors ? Object.values(err.errors)[0][0] : null;
        throw new Error(first || err.message || 'Request failed');
    }
    return res.status === 204 ? null : res.json();
}

function showError(msg) {
    const el = document.getElementById('error');
    el.textContent = msg;
    el.classList.toggle('hidden', !msg);
}

function cardHtml(t) {
    const due = t.due_date
        ? `<span class="${t.overdue ? 'text-red-600 font-medium' : 'text-slate-500'}">${esc(t.due_date)}</span>` : '';
    const holiday = t.due_on_holiday
        ? `<div class="text-xs text-amber-600 mt-1">⚠ Due on a public holiday: ${esc(t.due_on_holiday)}</div>` : '';
    return `
    <div draggable="true" data-id="${t.id}" class="card bg-white border rounded-lg p-3 shadow-sm cursor-grab">
        <div class="flex justify-between items-start gap-2">
            <div class="font-medium text-sm">${esc(t.title)}</div>
            <button data-del="${t.id}" class="text-slate-300 hover:text-red-500 text-xs" title="Delete">✕</button>
        </div>
        <div class="flex items-center gap-2 mt-2 text-xs">
            <span class="px-2 py-0.5 rounded ${PRIORITY_CLASS[t.priority]}">${esc(t.priority)}</span>
            ${due}
            <span class="ml-auto text-slate-500">${t.assignee ? esc(t.assignee.name) : ''}</span>
        </div>
        ${holiday}
    </div>`;
}

function render() {
    const board = document.getElementById('board');
    board.innerHTML = COLUMNS.map((col) => {
        const items = tasks.filter((t) => t.status === col.key);
        return `
        <section data-col="${col.key}" class="column bg-slate-100 rounded-lg p-3 min-h-[200px]">
            <h3 class="text-sm font-semibold mb-3 flex justify-between">${col.label}<span class="text-slate-400">${items.length}</span></h3>
            <div class="space-y-2">${items.map(cardHtml).join('')}</div>
        </section>`;
    }).join('');

    board.querySelectorAll('.card').forEach((card) => {
        card.addEventListener('dragstart', (e) => e.dataTransfer.setData('text/plain', card.dataset.id));
    });
    board.querySelectorAll('.column').forEach((col) => {
        col.addEventListener('dragover', (e) => e.preventDefault());
        col.addEventListener('drop', async (e) => {
            e.preventDefault();
            const id = Number(e.dataTransfer.getData('text/plain'));
            const task = tasks.find((t) => t.id === id);
            if (!task || task.status === col.dataset.col) return;
            try {
                const { data } = await api(`/ajax/tasks/${id}`, 'PATCH', { status: col.dataset.col });
                tasks = tasks.map((t) => (t.id === id ? data : t));
                showError('');
                render();
            } catch (err) { showError(err.message); }
        });
    });
    board.querySelectorAll('[data-del]').forEach((btn) => {
        btn.addEventListener('click', async () => {
            if (!confirm('Delete this task?')) return;
            try {
                await api(`/ajax/tasks/${btn.dataset.del}`, 'DELETE');
                tasks = tasks.filter((t) => t.id !== Number(btn.dataset.del));
                render();
            } catch (err) { showError(err.message); }
        });
    });
}

document.getElementById('assignee').insertAdjacentHTML('beforeend',
    TEAM.map((u) => `<option value="${u.id}">${esc(u.name)}</option>`).join(''));

document.getElementById('new-task').addEventListener('submit', async (e) => {
    e.preventDefault();
    const f = new FormData(e.target);
    const payload = Object.fromEntries([...f.entries()].filter(([, v]) => v !== ''));
    try {
        const { data } = await api(`/ajax/projects/${PROJECT_ID}/tasks`, 'POST', payload);
        tasks.push(data);
        e.target.reset();
        showError('');
        render();
    } catch (err) { showError(err.message); }
});

(async () => {
    try {
        const { data } = await api(`/ajax/projects/${PROJECT_ID}/tasks`);
        tasks = data;
        render();
    } catch (err) { showError(err.message); }
})();
</script>
@endsection
