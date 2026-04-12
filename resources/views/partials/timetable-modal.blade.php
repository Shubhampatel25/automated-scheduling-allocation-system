{{--
    Reusable timetable popup modal.

    Include with:
        @include('partials.timetable-modal', ['slotRouteBase' => url('admin/schedule')])
        @include('partials.timetable-modal', ['slotRouteBase' => url('hod/timetable')])

    Trigger from any button:
        onclick="openTimetableModal(id, 'Dept Name', 'Fall 2026', 3)"
--}}

<style>
.btn-view-tt {
    padding: 5px 14px; background: #6366f1; color: #fff;
    border: none; border-radius: 7px; font-size: 0.78rem;
    font-weight: 600; cursor: pointer; font-family: inherit;
}
.btn-view-tt:hover { background: #4f46e5; }

/* ── Modal overlay ─────────────────────────────────────────────── */
#tt-modal-overlay {
    display: none; position: fixed; inset: 0;
    background: rgba(15,23,42,.55); z-index: 1000;
    align-items: center; justify-content: center; padding: 20px;
}
#tt-modal-overlay.open { display: flex; }
#tt-modal {
    background: #fff; border-radius: 14px;
    width: 100%; max-width: 980px; max-height: 90vh;
    display: flex; flex-direction: column;
    box-shadow: 0 20px 60px rgba(0,0,0,.25); overflow: hidden;
}
#tt-modal-header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 18px 24px; border-bottom: 1px solid #e5e7eb; flex-shrink: 0;
}
#tt-modal-header h3 { font-size: 1.05rem; font-weight: 700; color: #1e293b; margin: 0; }
#tt-modal-meta { font-size: .8rem; color: #64748b; margin-top: 3px; }
.modal-close-btn {
    width: 32px; height: 32px; border-radius: 8px; border: none;
    background: #f1f5f9; color: #64748b; font-size: 1.2rem;
    cursor: pointer; display: flex; align-items: center; justify-content: center;
    flex-shrink: 0; line-height: 1;
}
.modal-close-btn:hover { background: #e2e8f0; color: #1e293b; }
#tt-modal-body { overflow-y: auto; padding: 20px 24px; flex: 1; }

/* Timetable grid inside modal */
.modal-tt-wrap { overflow-x: auto; }
table.modal-tt { width: 100%; border-collapse: collapse; min-width: 680px; font-size: .8rem; }
table.modal-tt th {
    padding: 9px 8px; background: #6366f1; color: #fff;
    text-align: center; font-weight: 600; font-size: .76rem;
}
table.modal-tt th.time-col { background: #4f46e5; width: 105px; }
table.modal-tt td { padding: 5px; border: 1px solid #e5e7eb; vertical-align: top; height: 72px; }
table.modal-tt td.time-lbl {
    background: #f8fafc; font-weight: 600; color: #475569;
    font-size: .74rem; text-align: center; vertical-align: middle;
}
.m-slot {
    border-radius: 7px; padding: 6px 8px; height: 100%;
    display: flex; flex-direction: column; justify-content: center; gap: 2px;
    border-left: 3px solid #6366f1; background: #eef2ff;
}
.m-slot.lab { background: #fef3c7; border-left-color: #d97706; }
.m-slot .sc { font-weight: 700; color: #1e293b; font-size: .75rem; line-height: 1.2; }
.m-slot .tc { font-size: .68rem; color: #4f46e5; }
.m-slot.lab .tc { color: #92400e; }
.m-slot .rm {
    display: inline-block; background: #6366f1; color: #fff;
    border-radius: 4px; padding: 1px 5px; font-size: .65rem; font-weight: 700;
}
.m-slot.lab .rm { background: #d97706; }
.m-slot .cp {
    display: inline-block; font-size: .63rem; font-weight: 700;
    background: rgba(99,102,241,.12); color: #4f46e5;
    border-radius: 4px; padding: 1px 5px; margin-top: 1px;
}
.m-slot.lab .cp { background: rgba(217,119,6,.12); color: #92400e; }
.m-slot .term-badge {
    display: inline-block; font-size: .6rem; font-weight: 700;
    background: #0ea5e9; color: #fff;
    border-radius: 4px; padding: 1px 5px; margin-top: 1px;
}
.m-slot .retake-badge {
    display: inline-block; font-size: .6rem; font-weight: 700;
    background: #dc2626; color: #fff;
    border-radius: 4px; padding: 1px 6px; margin-top: 1px;
    letter-spacing: .03em;
}
.m-slot.retake { border-left-color: #dc2626; background: #fff5f5; }
.m-slot.retake .sc { color: #7f1d1d; }
.m-slot.retake .tc { color: #dc2626; }
.modal-empty { text-align: center; padding: 40px 20px; color: #94a3b8; }
#tt-modal-loading { text-align: center; padding: 48px; color: #64748b; font-size: .9rem; }
.multi-term-notice {
    display:inline-flex; align-items:center; gap:6px;
    background:#eff6ff; border:1px solid #bfdbfe; border-radius:8px;
    padding:6px 12px; font-size:.78rem; color:#1d4ed8; margin-bottom:12px;
}
</style>

{{-- Modal HTML --}}
<div id="tt-modal-overlay" onclick="ttHandleOverlayClick(event)">
    <div id="tt-modal">
        <div id="tt-modal-header">
            <div>
                <h3 id="tt-modal-title">Timetable</h3>
                <div id="tt-modal-meta"></div>
            </div>
            <button class="modal-close-btn" onclick="ttCloseModal()" title="Close">&times;</button>
        </div>
        <div id="tt-modal-body">
            <div id="tt-modal-loading">Loading timetable&hellip;</div>
            <div id="tt-modal-content" style="display:none"></div>
        </div>
    </div>
</div>

<script>
(function () {
    const SLOT_BASE = '{{ $slotRouteBase }}';
    const DAYS  = ['Monday','Tuesday','Wednesday','Thursday','Friday'];
    const TIMES = [
        { label: '08:00 \u2013 09:30', start: '08:00' },
        { label: '09:40 \u2013 11:10', start: '09:40' },
        { label: '11:20 \u2013 12:50', start: '11:20' },
        { label: '13:50 \u2013 15:20', start: '13:50' },
        { label: '15:30 \u2013 17:00', start: '15:30' },
    ];

    window.openTimetableModal = function (id, dept, termYear, semester) {
        const semLabel = semester > 0 ? ' \u2014 Semester ' + semester : '';
        document.getElementById('tt-modal-title').textContent = dept + semLabel;
        document.getElementById('tt-modal-meta').textContent  = termYear;
        document.getElementById('tt-modal-loading').style.display = 'block';
        document.getElementById('tt-modal-loading').textContent   = 'Loading timetable\u2026';
        document.getElementById('tt-modal-content').style.display = 'none';
        document.getElementById('tt-modal-content').innerHTML     = '';
        document.getElementById('tt-modal-overlay').classList.add('open');
        document.body.style.overflow = 'hidden';

        fetch(SLOT_BASE + '/' + id + '/slots', {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            document.getElementById('tt-modal-loading').style.display = 'none';
            const el = document.getElementById('tt-modal-content');
            el.innerHTML = renderTtGrid(data.slots, data.timetable);
            el.style.display = 'block';
        })
        .catch(() => {
            document.getElementById('tt-modal-loading').textContent = 'Failed to load. Please try again.';
        });
    };

    window.ttCloseModal = function () {
        document.getElementById('tt-modal-overlay').classList.remove('open');
        document.body.style.overflow = '';
    };

    window.ttHandleOverlayClick = function (e) {
        if (e.target === document.getElementById('tt-modal-overlay')) ttCloseModal();
    };

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') ttCloseModal();
    });

    function renderTtGrid(slots, meta) {
        const map = {};
        slots.forEach(function (s) {
            const key = s.day + '|' + s.start;
            if (!map[key]) map[key] = [];
            map[key].push(s);
        });

        let html = '<div class="modal-tt-wrap">';

        if (!slots.length) {
            return html + '<div class="modal-empty">&#128197; No slots scheduled yet.</div></div>';
        }

        // Detect whether multiple terms are present
        const terms = [...new Set(slots.map(s => (s.term || '').trim()).filter(Boolean))];
        const isMultiTerm = terms.length > 1;

        const status = (meta && meta.status) ? meta.status : '';
        const sc = status === 'active'
            ? 'background:#d1fae5;color:#065f46'
            : 'background:#fef3c7;color:#92400e';

        html += '<p style="margin:0 0 10px;font-size:.82rem;color:#64748b;">';
        if (status && status !== '—') {
            html += 'Status: <span style="display:inline-block;padding:2px 10px;border-radius:20px;font-size:.75rem;font-weight:600;' + sc + '">'
                  + status.charAt(0).toUpperCase() + status.slice(1)
                  + '</span> &nbsp;&middot;&nbsp; ';
        }
        html += slots.length + ' slot(s)</p>';

        if (isMultiTerm) {
            html += '<div class="multi-term-notice">'
                  + '&#8505;&#65039; This teacher is active in multiple terms: '
                  + '<strong>' + terms.map(ttEsc).join(' &amp; ') + '</strong>'
                  + '. Each slot shows its term below.'
                  + '</div>';
        }

        html += '<table class="modal-tt"><thead><tr><th class="time-col">Time</th>';
        DAYS.forEach(function (d) { html += '<th>' + d + '</th>'; });
        html += '</tr></thead><tbody>';

        TIMES.forEach(function (t) {
            html += '<tr><td class="time-lbl">' + t.label + '</td>';
            DAYS.forEach(function (d) {
                const cell = map[d + '|' + t.start] || [];
                html += '<td>';
                if (!cell.length) {
                    html += '<span style="color:#cbd5e1;font-size:.72rem;">&#8212;</span>';
                } else {
                    cell.forEach(function (s) {
                        const lab     = s.component === 'lab';
                        const retake  = !!s.is_retake;
                        const termStr = (s.term || '').trim();
                        let cls = 'm-slot';
                        if (retake) cls += ' retake';
                        else if (lab) cls += ' lab';
                        html += '<div class="' + cls + '">'
                              + '<div class="sc">' + ttEsc(s.course) + '</div>'
                              + '<div class="tc">' + ttEsc(s.teacher) + '</div>'
                              + '<div><span class="rm">&#127968; ' + ttEsc(s.room) + '</span> '
                              + '<span class="cp">' + ttEsc(s.component) + '</span>'
                              + (retake  ? ' <span class="retake-badge">&#8635; Retake</span>' : '')
                              + (termStr ? ' <span class="term-badge">&#128337; ' + ttEsc(termStr) + '</span>' : '')
                              + '</div>'
                              + '</div>';
                    });
                }
                html += '</td>';
            });
            html += '</tr>';
        });

        html += '</tbody></table></div>';
        return html;
    }

    function ttEsc(str) {
        return String(str ?? '')
            .replace(/&/g,'&amp;').replace(/</g,'&lt;')
            .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
})();
</script>
