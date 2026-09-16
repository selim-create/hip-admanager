(function(){
  'use strict';

  const $=(selector,root=document)=>root.querySelector(selector);
  const $$=(selector,root=document)=>Array.from(root.querySelectorAll(selector));

  function slug(value){
    return String(value||'')
      .toLocaleLowerCase('tr')
      .normalize('NFD').replace(/[\u0300-\u036f]/g,'')
      .replace(/ı/g,'i').replace(/ğ/g,'g').replace(/ş/g,'s').replace(/ç/g,'c').replace(/ö/g,'o').replace(/ü/g,'u')
      .replace(/[^a-z0-9_-]+/g,'_').replace(/_+/g,'_').replace(/^[_-]+|[_-]+$/g,'');
  }

  function wireRepeaters(){
    $$('[data-add-row]').forEach(button=>{
      button.addEventListener('click',()=>{
        const target=$(button.dataset.addRow);
        const template=$(button.dataset.template);
        if(!target||!template)return;
        target.insertAdjacentHTML('beforeend',template.innerHTML.trim());
        target.dispatchEvent(new Event('change',{bubbles:true}));
      });
    });
    document.addEventListener('click',event=>{
      const button=event.target.closest('[data-remove-row]');
      if(!button)return;
      const row=button.closest('[data-repeat-row]');
      if(row){row.remove();document.dispatchEvent(new Event('change',{bubbles:true}));}
    });
  }

  function wireSlotEditor(){
    const form=$('[data-slot-form]');
    if(!form)return;
    const name=$('#hip-slot-name',form);
    const key=$('#hip-slot-key',form);
    const placement=$('#hip-placement-key',form);
    const path=$('#hip-ad-unit-path',form);
    let keyTouched=Boolean(key&&key.value);
    let placementTouched=Boolean(placement&&placement.value);

    if(key)key.addEventListener('input',()=>{keyTouched=true;});
    if(placement)placement.addEventListener('input',()=>{placementTouched=true;});
    if(name)name.addEventListener('input',()=>{
      if(key&&!keyTouched)key.value=slug(name.value);
      if(placement&&!placementTouched)placement.value=slug(name.value);
      updatePreview();
    });
    [key,placement,path].filter(Boolean).forEach(field=>field.addEventListener('input',updatePreview));
    document.addEventListener('change',updatePreview);
    document.addEventListener('input',event=>{
      if(event.target.matches('[name="size_width[]"],[name="size_height[]"]'))updatePreview();
    });

    function updatePreview(){
      const preview=$('[data-slot-preview]');
      if(!preview)return;
      const sizes=$$('[data-size-row]',form).map(row=>{
        const w=$('[name="size_width[]"]',row)?.value;
        const h=$('[name="size_height[]"]',row)?.value;
        return w&&h?`${w}×${h}`:'';
      }).filter(Boolean);
      $('[data-preview-key]',preview).textContent=(key&&key.value)||'—';
      $('[data-preview-placement]',preview).textContent=(placement&&placement.value)||'—';
      $('[data-preview-path]',preview).textContent=(path&&path.value)||'—';
      $('[data-preview-sizes]',preview).textContent=sizes.length?sizes.join(' · '):'Boyut eklenmedi';
    }

    function refreshVisibility(){
      const enabled=$('[name="refresh_enabled"]',form);
      const fields=$('[data-refresh-fields]',form);
      if(fields)fields.hidden=!(enabled&&enabled.checked);
    }
    const refresh=$('[name="refresh_enabled"]',form);
    if(refresh){refresh.addEventListener('change',refreshVisibility);refreshVisibility();}

    form.addEventListener('submit',event=>{
      let valid=true;
      $$('[required]',form).forEach(field=>{
        field.classList.toggle('invalid',!field.value.trim());
        if(!field.value.trim())valid=false;
      });
      if(path&&path.value&&path.value.charAt(0)!=='/'){
        path.classList.add('invalid');
        valid=false;
      }
      const refreshEnabled=$('[name="refresh_enabled"]',form);
      const trigger=$('[name="refresh_trigger"]',form);
      const interval=$('[name="refresh_interval"]',form);
      if(refreshEnabled?.checked&&trigger?.value!=='user_action'&&Number(interval?.value||0)<30){
        interval?.classList.add('invalid');
        alert(window.HIPAdsAdmin?.refreshMinNotice||'Refresh interval must be at least 30 seconds.');
        valid=false;
      }
      if(!valid){event.preventDefault();form.querySelector('.invalid')?.focus();}
      else{form.dataset.submitting='1';}
    });

    let dirty=false;
    form.addEventListener('input',()=>{dirty=true;});
    form.addEventListener('change',()=>{dirty=true;});
    window.addEventListener('beforeunload',event=>{
      if(dirty&&form.dataset.submitting!=='1'){event.preventDefault();event.returnValue='';}
    });
    updatePreview();
  }

  function wireDelete(){
    $$('[data-confirm-delete]').forEach(form=>{
      form.addEventListener('submit',event=>{
        if(!confirm(window.HIPAdsAdmin?.confirmDelete||'Delete this slot?'))event.preventDefault();
      });
    });
    $$('[data-confirm-import]').forEach(form=>{
      form.addEventListener('submit',event=>{
        if(!confirm(window.HIPAdsAdmin?.confirmImport||'Apply import changes?'))event.preventDefault();
      });
    });
  }

  function wireCopy(){
    $$('[data-copy]').forEach(button=>{
      button.addEventListener('click',async()=>{
        const value=button.dataset.copy||'';
        try{
          await navigator.clipboard.writeText(value);
          const old=button.textContent;
          button.textContent='Kopyalandı';
          setTimeout(()=>button.textContent=old,1200);
        }catch(e){/* clipboard can be unavailable on insecure origins */}
      });
    });
  }

  function wireSettings(){
    const form=$('[data-settings-form]');
    if(!form)return;
    const lazy=$('[name="enable_lazy_load"]',form);
    const group=$('[data-lazy-fields]',form);
    const update=()=>{if(group)group.hidden=!(lazy&&lazy.checked);};
    lazy?.addEventListener('change',update);update();
  }

  document.addEventListener('DOMContentLoaded',()=>{
    wireRepeaters();
    wireSlotEditor();
    wireDelete();
    wireCopy();
    wireSettings();
  });
})();
