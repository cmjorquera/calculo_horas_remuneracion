(function(){
  const table = document.getElementById('empTable');
  if(!table) return;

  const tbody = table.querySelector('tbody');
  const search = document.getElementById('empSearch');
  const clearBtn = document.getElementById('empClear');
  const ths = Array.from(table.querySelectorAll('thead th.sortable'));

  let sortState = { index: -1, dir: 'asc', type: 'text' };

  // ---- helpers
  function toast(msg){
    let t = document.querySelector('.emp-toast');
    if(!t){
      t = document.createElement('div');
      t.className = 'emp-toast';
      document.body.appendChild(t);
    }
    t.textContent = msg;
    t.classList.add('show');
    setTimeout(()=>t.classList.remove('show'), 1400);
  }

  async function copyText(text){
    try{
      await navigator.clipboard.writeText(text);
      toast('Copiado ✅');
    }catch(e){
      // fallback
      const ta = document.createElement('textarea');
      ta.value = text;
      document.body.appendChild(ta);
      ta.select();
      document.execCommand('copy');
      ta.remove();
      toast('Copiado ✅');
    }
  }

  function normalize(s){
    return (s ?? '')
      .toString()
      .toLowerCase()
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g,''); // quita acentos
  }

  function timeToMinutes(hhmm){
    // "40:00" => 2400
    const m = (hhmm || '00:00').match(/^(\d{1,3}):(\d{2})$/);
    if(!m) return 0;
    return (parseInt(m[1],10)*60) + parseInt(m[2],10);
  }

  function minutesTextToNumber(txt){
    // "45 min" => 45
    const m = (txt || '').match(/-?\d+/);
    return m ? parseInt(m[0],10) : 0;
  }

  function getCellValue(tr, index){
    const td = tr.children[index];
    if(!td) return '';
    return td.getAttribute('data-value') || td.textContent.trim();
  }

  function compare(a, b, type){
    if(type === 'number'){
      return (parseFloat(a)||0) - (parseFloat(b)||0);
    }
    if(type === 'time'){
      return timeToMinutes(a) - timeToMinutes(b);
    }
    if(type === 'minutes'){
      return minutesTextToNumber(a) - minutesTextToNumber(b);
    }
    // text
    a = normalize(a);
    b = normalize(b);
    return a.localeCompare(b, 'es');
  }

  function applyFilter(){
    const q = normalize(search.value.trim());
    const rows = Array.from(tbody.querySelectorAll('tr'));
    rows.forEach(tr=>{
      const hay = normalize(tr.getAttribute('data-filter') || tr.textContent);
      tr.style.display = (q === '' || hay.includes(q)) ? '' : 'none';
    });
  }

  function applySort(thIndex, type){
    const rows = Array.from(tbody.querySelectorAll('tr'));
    const visibleRows = rows.filter(r => r.style.display !== 'none');

    // toggle dir
    if(sortState.index === thIndex){
      sortState.dir = (sortState.dir === 'asc') ? 'desc' : 'asc';
    }else{
      sortState.index = thIndex;
      sortState.dir = 'asc';
      sortState.type = type || 'text';
    }

    // UI th classes
    ths.forEach((th, i)=>{
      th.classList.remove('is-sorted','sort-asc','sort-desc');
      if(i === sortState.index){
        th.classList.add('is-sorted', sortState.dir === 'asc' ? 'sort-asc' : 'sort-desc');
      }
    });

    visibleRows.sort((r1, r2)=>{
      const v1 = getCellValue(r1, thIndex);
      const v2 = getCellValue(r2, thIndex);
      const c = compare(v1, v2, sortState.type);
      return sortState.dir === 'asc' ? c : -c;
    });

    // reinsert (manteniendo filas ocultas al final sin tocar)
    visibleRows.forEach(r => tbody.appendChild(r));
  }

  // ---- Search events
  search?.addEventListener('input', applyFilter);
  clearBtn?.addEventListener('click', ()=>{
    search.value = '';
    applyFilter();
    search.focus();
  });

  // ---- Sort events
  ths.forEach((th, index)=>{
    const type = th.getAttribute('data-type') || 'text';
    th.addEventListener('click', (ev)=>{
      // si clickeas un botón dentro del TH (copiar columna), no ordenar
      if(ev.target.closest('button')) return;
      applySort(index, type);
    });

    // Copiar columna completa
    const btn = th.querySelector('.th-copy-all');
    if(btn){
      btn.addEventListener('click', async ()=>{
        const colName = th.innerText.replace(/\s+/g,' ').trim();
        const rows = Array.from(tbody.querySelectorAll('tr')).filter(r => r.style.display !== 'none');
        const values = rows.map(r => getCellValue(r, index));
        const text = colName + "\n" + values.join("\n");
        await copyText(text);
      });
    }
  });

  // ---- Copy cell events (delegation)
  table.addEventListener('click', async (e)=>{
    const btn = e.target.closest('.btn-copy');
    if(!btn) return;
    const td = btn.closest('td');
    const val = td?.getAttribute('data-value') || td?.innerText.trim() || '';
    await copyText(val);
  });

  // ---- Default sort (opcional): por N°
  applySort(0, 'number');

})();
