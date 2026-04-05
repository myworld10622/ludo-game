@extends('admin.layouts.app')

@section('title', 'Homepage Tournament Cards')
@section('heading', 'Homepage Cards')
@section('subheading', 'Homepage par dikhne wale tournament cards manage karo')

@php
    $editCard = session('editCard') ?? null;
    $isEdit   = (bool) $editCard;
@endphp

@section('content')
<div class="stack">

    {{-- ── Hero Banner ── --}}
    <div class="panel" style="background:linear-gradient(135deg,#0f172a,#153e75);color:#fff;border:none;">
        <div style="display:flex;justify-content:space-between;gap:18px;align-items:flex-start;flex-wrap:wrap;">
            <div>
                <div class="badge" style="background:rgba(255,255,255,0.14);color:#fff;">Homepage Control</div>
                <h2 style="margin:12px 0 8px;font-size:26px;">Tournament Cards — Homepage Display</h2>
                <div style="color:rgba(255,255,255,0.80);max-width:700px;line-height:1.7;">
                    Yahan se homepage par dikhne wale <strong>3 tournament cards</strong> edit karo.
                    Changes immediately live ho jaate hain — page refresh hote hi.
                </div>
            </div>
            <a href="{{ url('/') }}" target="_blank" class="btn" style="background:rgba(255,255,255,0.18);white-space:nowrap;">
                🌐 View Homepage
            </a>
        </div>
    </div>

    {{-- ── Stats ── --}}
    <div class="stats">
        <div class="stat-card">
            <div class="stat-label">Total Cards</div>
            <div class="stat-value">{{ $cards->count() }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Visible on Homepage</div>
            <div class="stat-value">{{ $cards->where('is_visible', true)->count() }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Hidden Cards</div>
            <div class="stat-value">{{ $cards->where('is_visible', false)->count() }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Linked to Real Tournament</div>
            <div class="stat-value">{{ $cards->whereNotNull('tournament_id')->count() }}</div>
        </div>
    </div>

    {{-- ── Cards Preview ── --}}
    <div class="panel">
        <div class="header-row">
            <div>
                <strong>Live Preview</strong>
                <div class="muted" style="margin-top:4px;">Homepage par exactly aise dikhenge</div>
            </div>
        </div>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:16px;margin-top:8px;" id="cardsPreviewGrid">
            @forelse($cards as $card)
                @php
                    $colors = [
                        'gold'   => ['border'=>'#FFD700','top'=>'linear-gradient(90deg,#FFD700,#FF9500)','badge_bg'=>'rgba(255,215,0,0.15)','badge_color'=>'#CC7700'],
                        'blue'   => ['border'=>'#1A6BFF','top'=>'linear-gradient(90deg,#1A6BFF,#00C3FF)','badge_bg'=>'rgba(26,107,255,0.12)','badge_color'=>'#1A6BFF'],
                        'purple' => ['border'=>'#7B2FBE','top'=>'linear-gradient(90deg,#7B2FBE,#C060FF)','badge_bg'=>'rgba(123,47,190,0.12)','badge_color'=>'#7B2FBE'],
                    ];
                    $c = $colors[$card->card_color] ?? $colors['gold'];
                @endphp
                <div style="background:#1A1A2E;border:1px solid {{ $c['border'] }}33;border-radius:18px;overflow:hidden;position:relative;opacity:{{ $card->is_visible ? '1' : '0.45' }};">
                    <div style="height:3px;background:{{ $c['top'] }};"></div>
                    <div style="padding:20px;">
                        {{-- Status badge --}}
                        <div style="display:inline-flex;align-items:center;gap:6px;font-size:11px;font-weight:700;letter-spacing:1px;text-transform:uppercase;padding:4px 12px;border-radius:50px;background:{{ $c['badge_bg'] }};color:{{ $c['badge_color'] }};border:1px solid {{ $c['badge_color'] }}44;margin-bottom:12px;">
                            @if($card->status_badge === 'live')
                                <span style="width:6px;height:6px;border-radius:50%;background:currentColor;display:inline-block;"></span>
                            @elseif($card->status_badge === 'soon')
                                🕐
                            @else
                                ✓
                            @endif
                            {{ $card->status_text }}
                        </div>

                        <div style="font-size:18px;font-weight:700;color:#F0F0FF;margin-bottom:6px;">
                            {{ $card->icon }} {{ $card->name }}
                        </div>
                        <div style="font-size:13px;color:#8888AA;margin-bottom:16px;line-height:1.5;">
                            {{ Str::limit($card->description, 80) }}
                        </div>

                        {{-- Meta row --}}
                        <div style="display:flex;gap:16px;padding-top:14px;border-top:1px solid rgba(255,255,255,0.06);">
                            @foreach([[$card->meta1_label,$card->meta1_value],[$card->meta2_label,$card->meta2_value],[$card->meta3_label,$card->meta3_value]] as [$lbl,$val])
                                <div>
                                    <div style="font-size:10px;color:#8888AA;text-transform:uppercase;letter-spacing:0.5px;">{{ $lbl }}</div>
                                    <div style="font-size:15px;font-weight:700;color:#FFD700;">{{ $val }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Hidden badge --}}
                    @if(!$card->is_visible)
                        <div style="position:absolute;top:10px;right:10px;background:#b42318;color:#fff;font-size:10px;font-weight:700;padding:3px 8px;border-radius:50px;">HIDDEN</div>
                    @endif
                </div>
            @empty
                <div class="muted">Koi card nahi hai. Neeche se create karo.</div>
            @endforelse
        </div>
    </div>

    {{-- ── Cards Table ── --}}
    <div class="panel">
        <div class="header-row">
            <strong>Manage Cards</strong>
            <button class="btn" data-modal-open="cardFormModal">+ Add New Card</button>
        </div>

        <div class="table-wrap responsive-table">
            <table>
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Card</th>
                        <th>Status Badge</th>
                        <th>Meta Stats</th>
                        <th>Color</th>
                        <th>Visible</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="sortableCards">
                    @forelse($cards as $card)
                        <tr data-id="{{ $card->id }}">
                            <td data-label="Order" style="cursor:grab;color:#aaa;font-size:20px;padding:0 8px;">⠿ {{ $card->sort_order }}</td>
                            <td data-label="Card">
                                <strong>{{ $card->icon }} {{ $card->name }}</strong>
                                <div class="muted" style="font-size:12px;margin-top:2px;">{{ Str::limit($card->description, 60) }}</div>
                                @if($card->tournament_id)
                                    <div style="font-size:11px;color:#17806d;margin-top:2px;">🔗 Linked Tournament #{{ $card->tournament_id }}</div>
                                @endif
                            </td>
                            <td data-label="Status">
                                <span class="badge @if($card->status_badge === 'live') off @endif">
                                    {{ $card->status_text }}
                                </span>
                            </td>
                            <td data-label="Meta Stats" style="font-size:12px;line-height:1.7;">
                                {{ $card->meta1_label }}: <strong>{{ $card->meta1_value }}</strong><br>
                                {{ $card->meta2_label }}: <strong>{{ $card->meta2_value }}</strong><br>
                                {{ $card->meta3_label }}: <strong>{{ $card->meta3_value }}</strong>
                            </td>
                            <td data-label="Color">
                                <span style="display:inline-flex;align-items:center;gap:6px;">
                                    <span style="width:12px;height:12px;border-radius:50%;background:{{ ['gold'=>'#FFD700','blue'=>'#1A6BFF','purple'=>'#7B2FBE'][$card->card_color] ?? '#ccc' }};"></span>
                                    {{ ucfirst($card->card_color) }}
                                </span>
                            </td>
                            <td data-label="Visible">
                                @if($card->is_visible)
                                    <span class="badge">✓ Visible</span>
                                @else
                                    <span class="badge off">Hidden</span>
                                @endif
                            </td>
                            <td data-label="Actions" style="white-space:nowrap;">
                                <div class="mobile-actions">
                                    <button class="btn btn-secondary" style="font-size:12px;padding:5px 10px;"
                                        onclick="openEditCard({{ $card->toJson() }})">Edit</button>

                                    <form method="POST" action="{{ route('admin.homepage-cards.destroy', $card) }}"
                                        onsubmit="return confirm('Delete this card?')">
                                        @csrf @method('DELETE')
                                        <button class="btn" style="background:#b42318;font-size:12px;padding:5px 10px;">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="muted" style="text-align:center;padding:24px;">Koi card nahi hai. "Add New Card" se banao.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($cards->count() > 1)
            <div style="margin-top:12px;padding:12px;background:#ecfdf3;border:1px solid #abefc6;border-radius:10px;font-size:13px;color:#067647;">
                💡 Rows ko drag karke order change karo — automatically save hoga.
            </div>
        @endif
    </div>

    {{-- ── API Info Panel ── --}}
    <div class="panel">
        <div class="header-row">
            <strong>🔌 Homepage Integration</strong>
        </div>
        <p style="color:var(--muted);font-size:14px;margin-bottom:12px;">
            Homepage ka <code>index.html</code> yeh API endpoint se cards fetch karta hai:
        </p>
        <div style="background:#f8fafc;border:1px solid var(--line);border-radius:10px;padding:14px;font-family:monospace;font-size:13px;">
            GET <strong>{{ url('/api/homepage-cards') }}</strong>
        </div>
        <p style="color:var(--muted);font-size:13px;margin-top:10px;">
            Response example:
        </p>
        <pre style="background:#f8fafc;border:1px solid var(--line);border-radius:10px;padding:14px;font-size:12px;overflow-x:auto;">[
  {
    "id": 1,
    "name": "Grand Sunday Classic",
    "icon": "🔥",
    "description": "...",
    "card_color": "gold",
    "status_badge": "live",
    "status_text": "Live Now",
    "meta1_label": "Prize Pool", "meta1_value": "₹25,000",
    "meta2_label": "Entry",      "meta2_value": "₹199",
    "meta3_label": "Players",   "meta3_value": "105/128"
  }
]</pre>
    </div>

</div>

{{-- ══ CREATE / EDIT MODAL ══ --}}
<div id="cardFormModal" class="modal-shell">
    <div class="modal-backdrop" data-modal-close="cardFormModal"></div>
    <div class="modal-card" style="max-width:680px;">
        <div class="modal-head">
            <div>
                <div style="font-size:20px;font-weight:700;" id="modalHeading">Add Homepage Card</div>
                <div class="muted">Homepage par dikhne wala tournament card</div>
            </div>
            <button type="button" class="modal-close" data-modal-close="cardFormModal">×</button>
        </div>

        <form method="POST" id="cardForm" class="stack">
            @csrf
            <input type="hidden" name="_method" id="formMethod" value="POST">
            <input type="hidden" name="card_id"  id="cardIdField" value="">

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">

                {{-- Name + Icon --}}
                <div>
                    <label>Tournament Name *</label>
                    <input type="text" name="name" id="f_name" placeholder="Grand Sunday Classic" required>
                </div>
                <div>
                    <label>Icon / Emoji</label>
                    <input type="text" name="icon" id="f_icon" placeholder="🔥" maxlength="10">
                </div>

                {{-- Description --}}
                <div style="grid-column:1/-1;">
                    <label>Description</label>
                    <textarea name="description" id="f_description" rows="2" placeholder="Short description..."></textarea>
                </div>

                {{-- Card Color --}}
                <div>
                    <label>Card Color *</label>
                    <select name="card_color" id="f_card_color">
                        <option value="gold">🟡 Gold</option>
                        <option value="blue">🔵 Blue</option>
                        <option value="purple">🟣 Purple</option>
                    </select>
                </div>

                {{-- Status Badge --}}
                <div>
                    <label>Status Badge *</label>
                    <select name="status_badge" id="f_status_badge" onchange="syncStatusText(this.value)">
                        <option value="live">🔴 Live Now</option>
                        <option value="open">🟢 Open Registration</option>
                        <option value="soon">🔵 Starting Soon</option>
                    </select>
                </div>

                {{-- Status Text --}}
                <div style="grid-column:1/-1;">
                    <label>Status Text (button mein dikhega)</label>
                    <input type="text" name="status_text" id="f_status_text" placeholder="Live Now" required>
                </div>

                {{-- Meta 1 --}}
                <div>
                    <label>Stat 1 — Label</label>
                    <input type="text" name="meta1_label" id="f_meta1_label" placeholder="Prize Pool" required>
                </div>
                <div>
                    <label>Stat 1 — Value</label>
                    <input type="text" name="meta1_value" id="f_meta1_value" placeholder="₹25,000" required>
                </div>

                {{-- Meta 2 --}}
                <div>
                    <label>Stat 2 — Label</label>
                    <input type="text" name="meta2_label" id="f_meta2_label" placeholder="Entry" required>
                </div>
                <div>
                    <label>Stat 2 — Value</label>
                    <input type="text" name="meta2_value" id="f_meta2_value" placeholder="₹199" required>
                </div>

                {{-- Meta 3 --}}
                <div>
                    <label>Stat 3 — Label</label>
                    <input type="text" name="meta3_label" id="f_meta3_label" placeholder="Players" required>
                </div>
                <div>
                    <label>Stat 3 — Value</label>
                    <input type="text" name="meta3_value" id="f_meta3_value" placeholder="105/128" required>
                </div>

                {{-- Optional: Link to real tournament --}}
                <div style="grid-column:1/-1;">
                    <label>Link to Real Tournament (optional)</label>
                    <select name="tournament_id" id="f_tournament_id">
                        <option value="">— No Link —</option>
                        @foreach($tournaments as $t)
                            <option value="{{ $t->id }}">{{ $t->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Visible toggle --}}
                <div style="grid-column:1/-1;display:flex;align-items:center;gap:10px;">
                    <input type="checkbox" name="is_visible" id="f_is_visible" value="1" checked style="width:auto;accent-color:#d96c2f;">
                    <label for="f_is_visible" style="margin:0;cursor:pointer;">Homepage par visible rakho</label>
                </div>
            </div>

            <div style="display:flex;gap:10px;flex-wrap:wrap;padding-top:8px;border-top:1px solid var(--line);margin-top:4px;">
                <button type="submit" class="btn" id="submitBtn">Create Card</button>
                <button type="button" class="btn btn-secondary" data-modal-close="cardFormModal">Cancel</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
// ── Modal open/close ─────────────────────────────────────────────
document.querySelectorAll('[data-modal-open]').forEach(btn => {
    btn.addEventListener('click', () => {
        document.getElementById(btn.getAttribute('data-modal-open'))?.classList.add('is-open');
    });
});
document.querySelectorAll('[data-modal-close]').forEach(btn => {
    btn.addEventListener('click', () => {
        document.getElementById(btn.getAttribute('data-modal-close'))?.classList.remove('is-open');
        resetForm();
    });
});

// ── Status text auto-fill ────────────────────────────────────────
function syncStatusText(val) {
    const map = { live: 'Live Now', open: 'Open Registration', soon: 'Starting Soon' };
    const el = document.getElementById('f_status_text');
    if (!el.dataset.manual) el.value = map[val] || '';
}
document.getElementById('f_status_text').addEventListener('input', function() {
    this.dataset.manual = '1';
});

// ── Reset form ───────────────────────────────────────────────────
function resetForm() {
    document.getElementById('cardForm').reset();
    document.getElementById('formMethod').value = 'POST';
    document.getElementById('cardIdField').value = '';
    document.getElementById('cardForm').action = '{{ route('admin.homepage-cards.store') }}';
    document.getElementById('modalHeading').textContent = 'Add Homepage Card';
    document.getElementById('submitBtn').textContent = 'Create Card';
    delete document.getElementById('f_status_text').dataset.manual;
}

// ── Open edit mode ───────────────────────────────────────────────
function openEditCard(card) {
    document.getElementById('modalHeading').textContent = 'Edit — ' + card.name;
    document.getElementById('submitBtn').textContent = 'Save Changes';
    document.getElementById('formMethod').value = 'PUT';
    document.getElementById('cardIdField').value = card.id;
    document.getElementById('cardForm').action = '/admin/homepage-cards/' + card.id;

    // Fill fields
    document.getElementById('f_name').value         = card.name         || '';
    document.getElementById('f_icon').value         = card.icon         || '';
    document.getElementById('f_description').value  = card.description  || '';
    document.getElementById('f_card_color').value   = card.card_color   || 'gold';
    document.getElementById('f_status_badge').value = card.status_badge || 'open';
    document.getElementById('f_status_text').value  = card.status_text  || '';
    document.getElementById('f_meta1_label').value  = card.meta1_label  || '';
    document.getElementById('f_meta1_value').value  = card.meta1_value  || '';
    document.getElementById('f_meta2_label').value  = card.meta2_label  || '';
    document.getElementById('f_meta2_value').value  = card.meta2_value  || '';
    document.getElementById('f_meta3_label').value  = card.meta3_label  || '';
    document.getElementById('f_meta3_value').value  = card.meta3_value  || '';
    document.getElementById('f_tournament_id').value = card.tournament_id || '';
    document.getElementById('f_is_visible').checked = !!card.is_visible;
    document.getElementById('f_status_text').dataset.manual = '1';

    document.getElementById('cardFormModal').classList.add('is-open');
}

// ── Drag-and-drop reorder (SortableJS via CDN) ────────────────────
const sortableEl = document.getElementById('sortableCards');
if (sortableEl && window.Sortable) {
    Sortable.create(sortableEl, {
        animation: 150,
        handle: 'td:first-child',
        onEnd: function() {
            const order = [...sortableEl.querySelectorAll('tr[data-id]')]
                            .map(tr => tr.dataset.id);
            fetch('{{ route('admin.homepage-cards.reorder') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ order })
            });
        }
    });
}
</script>
{{-- SortableJS for drag reorder --}}
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
@endpush
