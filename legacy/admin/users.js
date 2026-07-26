document.addEventListener('DOMContentLoaded', function(){
    const selector = document.getElementById('columnSelector');
    const presetSel = document.getElementById('presetSelector');
    const saveBtn = document.getElementById('savePresetBtn');
    const deleteBtn = document.getElementById('deletePresetBtn');

    // Load presets from server
    function loadPresets(){
        fetch('api/column_presets.php').then(r=>r.json()).then(res=>{
            if (!res.success) return;
            presetSel.innerHTML = '';
            presetSel.add(new Option('Select preset', ''));
            res.data.forEach(p=>{
                presetSel.add(new Option(p.name, p.name));
                presetSel.querySelector(`option[value="${p.name}"]`).dataset.columns = JSON.stringify(p.columns);
            });
            // also try to load global defaults
            fetch('api/column_presets.php?global=1').then(r=>r.json()).then(g=>{
                if (g.success && g.data && g.data.length){
                    // prepend global presets into selector
                    g.data.forEach(p=>{
                        const opt = new Option('(Global) ' + p.name, p.name);
                        opt.dataset.columns = JSON.stringify(p.columns);
                        presetSel.add(opt, presetSel.options[1]);
                    });
                }
            }).catch(()=>{});
        });
    }

    loadPresets();

    // Save preset
    saveBtn.addEventListener('click', ()=>{
        const name = prompt('Preset name');
        if (!name) return;
        const cols = getSelectedColumns();
        const form = new FormData(); form.append('action','save'); form.append('name',name); form.append('columns',JSON.stringify(cols));
        fetch('api/column_presets.php',{method:'POST', body: form}).then(r=>r.json()).then(res=>{ if (res.success){ loadPresets(); alert('Preset saved'); }});
    });

    // Apply preset
    presetSel.addEventListener('change', ()=>{
        const name = presetSel.value;
        if (!name) return;
        const opt = presetSel.selectedOptions[0];
        const cols = JSON.parse(opt.dataset.columns || '[]');
        // apply checkboxes and order
        const items = Array.from(selector.querySelectorAll('.list-group-item'));
        // reorder list based on cols
        cols.forEach(c => {
            const found = items.find(i => i.dataset.value === c);
            if (found) selector.appendChild(found);
        });
        // set checkboxes
        Array.from(selector.querySelectorAll('.list-group-item')).forEach(li=> li.querySelector('input[type="checkbox"]').checked = cols.includes(li.dataset.value));
        persistSelection();
    });

    // Delete preset
    deleteBtn.addEventListener('click', ()=>{
        const name = presetSel.value; if (!name) return alert('Select preset to delete');
        if (!confirm('Delete preset ' + name + '?')) return;
        const form = new FormData(); form.append('action','delete'); form.append('name',name);
        fetch('api/column_presets.php',{method:'POST', body: form}).then(r=>r.json()).then(res=>{ if (res.success){ loadPresets(); alert('Deleted'); }});
    });

    // If Sortable.js is available, use it for a smoother drag/drop experience
    if (window.Sortable) {
        Sortable.create(selector, {
            animation: 150,
            handle: '.col-label',
            onEnd: function () { persistSelection(); }
        });
    } else {
        // Fallback: native HTML5 drag-and-drop
        let dragSrc = null;
        function handleDragStart(e){ dragSrc = this; this.classList.add('dragging'); e.dataTransfer.effectAllowed = 'move'; }
        function handleDragOver(e){ if (e.preventDefault) e.preventDefault(); e.dataTransfer.dropEffect = 'move'; return false; }
        function handleDragEnter(){ this.classList.add('over'); }
        function handleDragLeave(){ this.classList.remove('over'); }
        function handleDrop(e){ if (e.stopPropagation) e.stopPropagation(); if (dragSrc !== this){ const list = this.parentNode; list.insertBefore(dragSrc, this.nextSibling); } return false; }
        function handleDragEnd(){ Array.from(selector.querySelectorAll('.list-group-item')).forEach(i=>{ i.classList.remove('over','dragging'); }); persistSelection(); }
        Array.from(selector.querySelectorAll('.list-group-item')).forEach(function(item){ item.addEventListener('dragstart', handleDragStart, false); item.addEventListener('dragenter', handleDragEnter, false); item.addEventListener('dragover', handleDragOver, false); item.addEventListener('dragleave', handleDragLeave, false); item.addEventListener('drop', handleDrop, false); item.addEventListener('dragend', handleDragEnd, false); });
    }

    // Persist selection and order
    function getSelectedColumns(){
        return Array.from(selector.querySelectorAll('.list-group-item')).filter(li=> li.querySelector('input[type="checkbox"]').checked).map(li=> li.dataset.value);
    }
    function persistSelection(){
        const cols = getSelectedColumns();
        localStorage.setItem('users_columns', JSON.stringify(cols));
    }
    // Restore from localStorage
    const saved = localStorage.getItem('users_columns');
    if (saved){
        try{ const cols = JSON.parse(saved); Array.from(selector.querySelectorAll('.list-group-item')).forEach(li=> li.querySelector('input[type="checkbox"]').checked = cols.includes(li.dataset.value)); }catch(_){}
    }
    // checkbox change
    Array.from(selector.querySelectorAll('input[type="checkbox"]')).forEach(cb=> cb.addEventListener('change', persistSelection));

    // Preview order button
    const previewBtn = document.getElementById('previewOrderBtn');
    if (previewBtn) {
        previewBtn.addEventListener('click', ()=>{
            const cols = Array.from(selector.querySelectorAll('.list-group-item')).map(li=> ({key: li.dataset.value, label: li.querySelector('.col-label').textContent, included: li.querySelector('input[type="checkbox"]').checked}));
            let html = '<div style="padding:20px;font-family:Arial, sans-serif"><h4>Preview Columns</h4><ol>';
            cols.forEach(c=> { if (c.included) html += '<li>' + c.label + ' (' + c.key + ')</li>'; });
            html += '</ol></div>';
            const w = window.open('','_blank');
            w.document.write(html); w.document.close();
        });
    }

    // Save as default (global preset)
    const saveDefaultBtn = document.getElementById('saveDefaultBtn');
    if (saveDefaultBtn) {
        saveDefaultBtn.addEventListener('click', ()=>{
            const name = 'default';
            const cols = getSelectedColumns();
            const form = new FormData(); form.append('action','save'); form.append('name',name); form.append('columns',JSON.stringify(cols)); form.append('global','1');
            fetch('api/column_presets.php',{method:'POST', body: form}).then(r=>r.json()).then(res=>{ if (res.success){ alert('Saved as default preset'); loadPresets(); }});
        });
    }

    // Schedule export
    const scheduleBtn = document.getElementById('scheduleExportBtn');
    if (scheduleBtn) {
        scheduleBtn.addEventListener('click', ()=>{
            const type = prompt('Export type (csv or pdf)', 'csv'); if (!type) return;
            const cron = prompt('Cron expression (e.g. 0 2 * * *)', '0 2 * * *'); if (!cron) return;
            const cols = getSelectedColumns();
            // capture current filter values from page
            const filters = {};
            const formEl = document.getElementById('searchForm');
            if (formEl) {
                new FormData(formEl).forEach((v,k)=> { filters[k]=v; });
            }
            const form = new FormData(); form.append('type', type); form.append('cron', cron); form.append('columns', JSON.stringify(cols)); form.append('filters', JSON.stringify(filters));
            fetch('api/schedule_export.php', { method: 'POST', body: form }).then(r=>r.json()).then(res=>{ if (res.success) alert('Scheduled: ' + res.data.job_id); else alert('Failed to schedule'); });
        });
    }

});


