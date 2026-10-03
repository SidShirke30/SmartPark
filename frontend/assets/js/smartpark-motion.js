
document.addEventListener('DOMContentLoaded',()=>{
 const io=new IntersectionObserver(es=>es.forEach(e=>e.isIntersecting&&e.target.classList.add('is-visible')),{threshold:.12});
 document.querySelectorAll('.reveal').forEach(x=>io.observe(x));
 document.querySelectorAll('[data-count]').forEach(el=>{
   const target=Number(el.dataset.count||0); let n=0; const step=Math.max(1,Math.ceil(target/45));
   const tick=()=>{n=Math.min(target,n+step);el.textContent=n.toLocaleString();if(n<target)requestAnimationFrame(tick)};tick();
 });
 document.querySelectorAll('[data-tilt]').forEach(card=>{
   card.addEventListener('mousemove',e=>{const r=card.getBoundingClientRect(),x=(e.clientX-r.left)/r.width-.5,y=(e.clientY-r.top)/r.height-.5;card.style.transform=`perspective(900px) rotateX(${y*-4}deg) rotateY(${x*5}deg) translateY(-5px)`});
   card.addEventListener('mouseleave',()=>card.style.transform='');
 });
});
function escapeHtml(v){return String(v??'').replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));}


/* ===== Scroll + Auto Rotation Controller ===== */
document.addEventListener('DOMContentLoaded',()=>{
  const stage=document.querySelector('.scroll-car-stage');
  const front=document.querySelector('.scroll-car-front');
  const back=document.querySelector('.scroll-car-back');
  const progress=document.querySelector('.scroll-car-progress i');
  if(!stage) return;

  document.body.classList.add('scroll-motion-ready');

  let targetScroll=window.scrollY || 0;
  let smoothScroll=targetScroll;
  let lastTime=performance.now();

  const clamp=(v,min,max)=>Math.max(min,Math.min(max,v));
  const maxScroll=()=>Math.max(1,document.documentElement.scrollHeight-window.innerHeight);

  function render(now){
    targetScroll=window.scrollY || 0;
    const total=maxScroll();
    const p=clamp(targetScroll/total,0,1);

    /* Scroll controls the major turn; the clock adds a subtle showroom spin. */
    const autoDeg=(now*0.010)%360;
    const scrollDeg=p*720;
    const deg=(autoDeg+scrollDeg)%360;

    smoothScroll += (targetScroll-smoothScroll)*0.075;
    const smoothP=clamp(smoothScroll/total,0,1);

    const side=Math.abs(Math.cos((deg%360)*Math.PI/180));
    const scale=1.04 + Math.sin(smoothP*Math.PI)*0.10;
    const x=Math.sin(smoothP*Math.PI*2)*Math.min(150,window.innerWidth*.11);
    const y=Math.cos(smoothP*Math.PI)*Math.min(42,window.innerHeight*.05);
    const z=-80 + Math.sin(smoothP*Math.PI)*90;
    const opacity=.12 + side*.18 + Math.sin(smoothP*Math.PI)*.08;

    stage.style.transform=`translate3d(${x}px,${y}px,${z}px) rotateY(${deg}deg) scale(${scale})`;
    stage.style.opacity=opacity.toFixed(3);
    stage.style.filter=`blur(${(1.5-side*1.5).toFixed(2)}px)`;

    /* A mirrored copy acts as the rear side so the flat reference image still feels 3D. */
    front.style.opacity=side>=0.08 ? '1' : '0';
    back.style.opacity=side<0.08 ? '1' : '0';
    back.style.transform=`scaleX(-1)`;

    if(progress) progress.style.width=(smoothP*100).toFixed(2)+'%';
    lastTime=now;
    requestAnimationFrame(render);
  }

  window.addEventListener('scroll',()=>{targetScroll=window.scrollY||0},{passive:true});
  window.addEventListener('resize',()=>{targetScroll=window.scrollY||0},{passive:true});
  requestAnimationFrame(render);
});

/* ===== Global Background + Scroll Animation Layer ===== */
document.addEventListener('DOMContentLoaded',()=>{
  if(document.body.classList.contains('sp-motion-active')) return;
  document.body.classList.add('sp-motion-active');

  const ambient=document.createElement('div');
  ambient.className='sp-ambient';
  ambient.innerHTML='<div class="sp-orb one"></div><div class="sp-orb two"></div><div class="sp-orb three"></div><div class="sp-grid-overlay"></div>';
  document.body.prepend(ambient);

  const progress=document.createElement('div');
  progress.className='sp-scroll-progress';
  document.body.appendChild(progress);

  let ticking=false;
  const update=()=>{
    const max=Math.max(1,document.documentElement.scrollHeight-window.innerHeight);
    const p=Math.max(0,Math.min(1,window.scrollY/max));
    progress.style.width=(p*100).toFixed(2)+'%';

    document.querySelectorAll('.sp-video-wrap,.sp-map-card,.sp-login-copy').forEach((el,i)=>{
      if(window.innerWidth>800){
        const r=el.getBoundingClientRect();
        const center=(r.top+r.height/2-window.innerHeight/2)/window.innerHeight;
        el.style.setProperty('--scroll-shift',`${Math.max(-12,Math.min(12,-center*12))}px`);
      }
    });
    ticking=false;
  };
  window.addEventListener('scroll',()=>{if(!ticking){requestAnimationFrame(update);ticking=true;}},{passive:true});
  window.addEventListener('resize',update,{passive:true});
  update();

  // Gentle pointer-parallax for the hero/video, without hijacking scrolling.
  const hero=document.querySelector('.sp-hero');
  const video=document.querySelector('.sp-video-wrap');
  if(hero && video && !window.matchMedia('(prefers-reduced-motion: reduce)').matches){
    hero.addEventListener('pointermove',e=>{
      const r=hero.getBoundingClientRect();
      const x=(e.clientX-r.left)/r.width-.5;
      const y=(e.clientY-r.top)/r.height-.5;
      video.style.transform=`translate3d(${x*8}px,${y*5}px,0) scale(1.008)`;
    },{passive:true});
    hero.addEventListener('pointerleave',()=>video.style.transform='',{passive:true});
  }
});
