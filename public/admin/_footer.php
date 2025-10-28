<?php // /arcadia/public/admin/_footer.php ?>
</main>
</div>
</div>

<<script>
  (function(){
  const btn = document.getElementById('btnMenu');
  const side = document.getElementById('sidenav');
  if(!btn || !side) return;
  const setInit=()=>{ if
  (matchMedia('(max-width:980px)').matches){side.dataset.collapsed='true';btn.setAttribute('aria-expanded','false');}else{side.dataset.collapsed='false';btn.setAttribute('aria-expanded','true');}};
  setInit(); addEventListener('resize', setInit);
  btn.addEventListener('click', ()=>{ const c=side.dataset.collapsed==='true'; side.dataset.collapsed=c?'false':'true';
  btn.setAttribute('aria-expanded', c?'true':'false'); });
  })();
  </script>

  </script>
  </body>

  </html>