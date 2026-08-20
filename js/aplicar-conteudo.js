(function(){
  function get(obj, path){
    return path.split('.').reduce(function(a,k){
      if(a==null) return a;
      return /^\d+$/.test(k) ? a[Number(k)] : a[k];
    }, obj);
  }
  function waLink(phone){
    var d = String(phone||'').replace(/\D/g,'');
    if(d.length===11) d = '55'+d;
    if(d.length===10) d = '55'+d;
    return 'https://wa.me/'+d;
  }
  fetch('data/conteudo.json?t=' + Date.now())
    .then(function(r){ return r.ok ? r.json() : null; })
    .then(function(data){
      if(!data) return;
      document.querySelectorAll('[data-k]').forEach(function(el){
        var v = get(data, el.getAttribute('data-k'));
        if(v==null || v==='') return;
        if(el.tagName==='A'){
          if(el.getAttribute('data-k').indexOf('email')!==-1) el.href = 'mailto:'+v;
          else if(el.getAttribute('data-k').indexOf('whatsapp')!==-1) el.href = waLink(v);
          el.textContent = v;
        } else {
          el.textContent = v;
        }
      });
      var w = get(data,'contato.whatsapp');
      if(w){
        var link = waLink(w);
        document.querySelectorAll('a[href^="https://wa.me/"]').forEach(function(a){ a.href = link; });
      }
      var mail = get(data,'contato.email');
      if(mail){
        document.querySelectorAll('a[href^="mailto:"]').forEach(function(a){
          a.href = 'mailto:'+mail;
          if(a.hasAttribute('data-k')===false && a.textContent.indexOf('@')!==-1) a.textContent = mail;
        });
      }
      var pix = get(data,'pix.chave');
      if(pix){
        var code = document.querySelector('#doar code');
        if(code) code.textContent = pix;
      }
    })
    .catch(function(){});
})();
