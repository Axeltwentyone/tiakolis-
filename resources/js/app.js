/* =========================================================
   Site Tiakolisé et fière
   Les pièces, prix et stocks viennent de la base (GET /api/catalogue) et se gèrent dans le back office (/admin).
   ========================================================= */
let PIECES = [];               // [{id, type, nom, couleur, prix, face, dos, stock:{S:20,…}}]
let TAILLES = ["S","M","L","XL"];
let COMMUNES = [], PAIEMENT = {};  // communes d'Abidjan (livraison Yango) ; {wave, lien, whatsapp}
const QMAX = 5;                // quantité max par ligne (idem PrecommandeController)
const fcfa = n => n.toLocaleString("fr-FR").replace(/\s/g," ")+" FCFA";
const piece = id => PIECES.find(p=>p.id===id);
const stockDe = (p,t) => p?.stock?.[t] ?? 0;
const stockTotal = p => TAILLES.reduce((s,t)=>s+stockDe(p,t),0);

/* Écrans des télés : remplacer par des captures du clip « Mélo Décalé » (images .jpg ou vidéos .mp4).
   En attendant, ce sont des images du shooting. */
const CLIP_SOURCES = Array.from({length:14},(_,i)=>`/assets/tv/still-${String(i+1).padStart(2,"0")}.jpg`);
const TV_VIDEOS = ["/assets/shoot-6569.mp4","/assets/shoot-6570.mp4"]; // télés qui jouent en vidéo
const FILM_WORDS = ["Mélo","Décalé","Fait","par","nous","pour","nous","Parce","que","notre","voix","compte","Monétisez","les","clips","Afro","Francophones"];
const RED = new Set(["Décalé","Afro","voix"]), OCRE = new Set(["Monétisez"]);

const reduce = matchMedia("(prefers-reduced-motion: reduce)").matches;

/* ---------- 0. Chargement : les mots montent, pause, puis le rideau glisse vers le haut ---------- */
(function intro(){
  const el=document.getElementById("intro"), html=document.documentElement;
  if(!html.classList.contains("intro-on")){ el.remove(); return; }
  try{ sessionStorage.setItem("tk-intro","1"); }catch{}
  let parti=false;
  const fin=()=>{ html.classList.remove("intro-on","intro-part"); el.remove(); };
  const part=()=>{
    if(parti) return; parti=true;
    html.classList.add("intro-part"); el.classList.add("is-out");
    el.addEventListener("transitionend",fin,{once:true});
    setTimeout(fin,1500); // filet de sécurité si transitionend ne vient pas
  };
  setTimeout(part, reduce?500:2100);
  el.addEventListener("pointerdown",part); // toucher/cliquer = passer
  addEventListener("keydown",part,{once:true});
})();

/* ---------- 1. Marquee : duplique le contenu pour une boucle sans couture ---------- */
document.querySelectorAll("[data-repeat]").forEach(t=>{ const h=t.innerHTML; t.innerHTML=h.repeat(4); });

/* ---------- Catalogue : chargé depuis l'API, rechargé après chaque précommande (le stock bouge) ---------- */
const catalogue = (function(){
  const ecouteurs=[];
  return {
    async charge(){
      const res=await fetch("/api/catalogue",{headers:{Accept:"application/json"}});
      if(!res.ok) throw new Error("catalogue "+res.status);
      const json=await res.json();
      PIECES=json.pieces; TAILLES=json.tailles; COMMUNES=json.communes; PAIEMENT=json.paiement;
      ecouteurs.forEach(f=>f());
    },
    surChange(f){ ecouteurs.push(f); },
  };
})();

/* ---------- Panier : [{piece, taille, quantite}], gardé dans le navigateur ---------- */
const panier = (function(){
  const CLE="tk-panier", ecouteurs=[];
  let lignes=[];
  try{ lignes=JSON.parse(localStorage.getItem(CLE))||[]; }catch{}
  const sauve=()=>{ try{ localStorage.setItem(CLE,JSON.stringify(lignes)); }catch{} ecouteurs.forEach(f=>f()); };
  // pièce retirée du catalogue : la ligne disparaît du panier
  catalogue.surChange(()=>{ lignes=lignes.filter(l=>piece(l.piece) && TAILLES.includes(l.taille)); sauve(); });
  return {
    get lignes(){ return lignes; },
    get nombre(){ return lignes.reduce((s,l)=>s+l.quantite,0); },
    get total(){ return lignes.reduce((s,l)=>s+l.quantite*(piece(l.piece)?.prix||0),0); },
    /** quantité max pour une ligne : 5, et jamais plus que le stock restant */
    max(id,taille){ return Math.min(QMAX,stockDe(piece(id),taille)); },
    ajoute(id,taille){
      const l=lignes.find(l=>l.piece===id && l.taille===taille);
      if(l){ if(l.quantite>=this.max(id,taille)) return false; l.quantite++; }
      else{ if(this.max(id,taille)<1) return false; lignes.push({piece:id,taille,quantite:1}); }
      sauve(); return true;
    },
    change(i,delta){ const l=lignes[i]; l.quantite=Math.max(1,Math.min(this.max(l.piece,l.taille),l.quantite+delta)); sauve(); },
    retire(i){ lignes.splice(i,1); sauve(); },
    vide(){ lignes=[]; sauve(); },
    surChange(f){ ecouteurs.push(f); f(); },
  };
})();

/* Pastille « Panier » */
(function(){
  const nb=document.getElementById("panier-nb");
  panier.surChange(()=>{ nb.textContent=panier.nombre; });
})();

/* ---------- 2. Collection : 1 t-shirt au centre, change au scroll, se retourne au survol ---------- */
const collection = (function(){
  const section=document.getElementById("collection"), slides=document.getElementById("slides"),
        names=document.getElementById("names"), dots=document.getElementById("dots"),
        count=document.getElementById("count"), detail=document.getElementById("detail"), hint=document.getElementById("hint"),
        prix=document.getElementById("prix"), stock=document.getElementById("stock"), sizes=document.getElementById("sizes"),
        ajout=document.getElementById("ajout"), live=document.getElementById("panier-live"), nb=document.getElementById("panier-nb"),
        scrollHint=document.getElementById("scroll-hint"), scrollTexte=document.getElementById("scroll-texte"), scrollNum=document.getElementById("scroll-num");
  const touch=matchMedia("(hover: none)").matches;
  if(touch) hint.textContent="Touche la pièce pour voir le dos";
  // mobile : carrousel horizontal (défile tout seul de droite à gauche, flèches, glisser) ; ordinateur : la pièce change au scroll
  const ecranMobile=matchMedia("(max-width: 760px)");
  let carrousel=ecranMobile.matches;
  let S=[], cur=-1, taille=null, t0;
  const LABEL="Ajouter au panier";

  function construit(){
    slides.innerHTML=names.innerHTML=dots.innerHTML="";
    placeDots();
    S=PIECES.map((p,k)=>{
      const f=document.createElement("figure"); f.className="slide";
      f.innerHTML=`<button class="tee" type="button" aria-label="Retourner le t-shirt ${p.nom} ${p.couleur}">
        <span class="tee__inner"><span class="tee__face"><img src="${p.face}" alt="${p.nom} ${p.couleur}, face"></span>
        <span class="tee__face tee__face--dos"><img src="${p.dos}" alt="${p.nom} ${p.couleur}, dos : Warning, monétisez les clips afro francophones"></span></span></button>`;
      f.querySelector(".tee").addEventListener("click",e=>e.currentTarget.classList.toggle("is-flipped"));
      slides.appendChild(f);
      const n=document.createElement("div"); n.className="rack__name"; n.textContent=p.nom; names.appendChild(n);
      const d=document.createElement("span"); dots.appendChild(d);
      d.addEventListener("click",()=>{ if(!carrousel || k===cur) return; geste(); set(k,k>cur?1:-1); });
      return {f,n,d};
    });
    sizes.innerHTML=TAILLES.map(t=>`<button type="button" role="radio" aria-checked="false" data-t="${t}">${t}</button>`).join("");
    cur=-1; if(carrousel) set(0); else onScroll();
  }

  /** tailles épuisées barrées, badge de stock, bouton « Épuisé » */
  function majStock(){
    const p=PIECES[cur]; if(!p) return;
    const total=stockTotal(p);
    if(taille && stockDe(p,taille)<1) taille=null; // taille choisie épuisée sur cette pièce
    sizes.querySelectorAll("button").forEach(b=>{
      const n=stockDe(p,b.dataset.t);
      b.disabled=n<1; b.setAttribute("aria-checked",b.dataset.t===taille);
      b.setAttribute("aria-label",n<1?`${b.dataset.t}, épuisé`:b.dataset.t);
    });
    const n=taille?stockDe(p,taille):total;
    stock.hidden=false;
    stock.classList.toggle("is-urgent",total===0 || n<=5);
    stock.textContent= total===0 ? "Épuisé"
      : taille && n<=10 ? `Plus que ${n} en ${taille}`
      : total<=30 ? `Plus que ${total} pièces`
      : "Série limitée";
    ajout.disabled=total===0;
    if(!ajout.classList.contains("is-done")) ajout.textContent=total===0?"Épuisé":LABEL;
  }

  /** dir : +1 = pièce suivante (arrive par la droite), -1 = précédente (arrive par la gauche), 0 = scroll (ordinateur) */
  function set(i,dir=0){
    if(i===cur) return; cur=i;
    if(carrousel && dir){
      // la pièce qui arrive est d'abord placée, sans animation, du côté d'où elle doit entrer
      for(const el of [S[i].f,S[i].n]){ el.style.transition="none"; el.classList.remove("is-active"); el.classList.toggle("is-before",dir<0); }
      void S[i].f.offsetWidth;
      for(const el of [S[i].f,S[i].n]) el.style.transition="";
    }
    S.forEach((o,k)=>{
      // carrousel : toutes les autres pièces sortent du côté opposé ; scroll : celles d'avant au-dessus, celles d'après en dessous
      const avant = carrousel ? (dir ? dir>0 : k<i) : k<i;
      for(const el of [o.f,o.n]){ el.classList.toggle("is-active",k===i); el.classList.toggle("is-before",k!==i && avant); }
      o.d.classList.toggle("is-active",k===i);
      if(k!==i) o.f.querySelector(".tee").classList.remove("is-flipped");
    });
    const p=PIECES[i];
    count.innerHTML=`${String(i+1).padStart(2,"0")}<small> / ${String(PIECES.length).padStart(2,"0")}</small>`;
    detail.textContent=`${p.type} · ${p.nom} · ${p.couleur}`;
    // repère de scroll : « pièce suivante » jusqu'à la dernière, puis rappel du retournement
    const fin=i===PIECES.length-1;
    scrollTexte.textContent= carrousel
      ? (touch?"Glisse ou touche les flèches":"Clique sur les flèches")
      : fin ? (touch?"Touche le t-shirt : le dos":"Survole le t-shirt : le dos") : "Défile pour la pièce suivante";
    scrollHint.classList.toggle("is-fin",fin && !carrousel);
    scrollNum.textContent=`${i+1}/${PIECES.length}`;
    prix.textContent=fcfa(p.prix);
    majStock();
  }
  function onScroll(){
    if(!PIECES.length || carrousel) return;
    const r=section.getBoundingClientRect(), span=section.offsetHeight-innerHeight;
    const prog=Math.min(.9999,Math.max(0,-r.top/span));
    set(Math.floor(prog*PIECES.length));
  }
  addEventListener("scroll",onScroll,{passive:true}); addEventListener("resize",onScroll);

  /* ----- carrousel mobile ----- */
  // mobile : les points passent en bas à droite, sur la ligne du compteur (dans le pied) ; ordinateur : à droite de l'écran
  const foot=section.querySelector(".rack__foot"), placeOrigine=dots.nextElementSibling;
  function placeDots(){
    section.classList.toggle("rack--carrousel",carrousel);
    section.style.height=carrousel?"":(N()+1)*100+"vh";
    if(carrousel) foot.prepend(dots); else placeOrigine.before(dots);
  }
  const N=()=>PIECES.length;
  const suivante=()=>set((cur+1)%N(),1), precedente=()=>set((cur-1+N())%N(),-1);
  // défilement auto : en pause quand le client agit (8 s), retourne un t-shirt, ouvre le panier ou ne voit pas la collection
  let dernierGeste=0, visible=false;
  const geste=()=>{ dernierGeste=Date.now(); };
  new IntersectionObserver(([e])=>{ visible=e.isIntersecting; },{threshold:.6}).observe(section);
  if(!reduce) setInterval(()=>{
    if(!carrousel || !N() || !visible || document.hidden || document.getElementById("tiroir").open) return;
    if(Date.now()-dernierGeste<8000 || S[cur]?.f.querySelector(".tee").classList.contains("is-flipped")) return;
    suivante();
  },4500);
  document.getElementById("suivante").addEventListener("click",()=>{ geste(); suivante(); });
  document.getElementById("precedente").addEventListener("click",()=>{ geste(); precedente(); });
  // glisser du doigt (horizontalement) pour changer de pièce
  const stage=section.querySelector(".rack__stage");
  let x0=null, y0=0;
  stage.addEventListener("touchstart",e=>{ geste(); if(!carrousel) return; x0=e.touches[0].clientX; y0=e.touches[0].clientY; },{passive:true});
  stage.addEventListener("touchend",e=>{
    if(x0===null) return;
    const dx=e.changedTouches[0].clientX-x0, dy=e.changedTouches[0].clientY-y0; x0=null;
    if(Math.abs(dx)>45 && Math.abs(dx)>Math.abs(dy)*1.3){ geste(); dx<0?suivante():precedente(); }
  },{passive:true});
  // passage ordinateur ↔ mobile (rotation, fenêtre redimensionnée)
  ecranMobile.addEventListener("change",e=>{
    carrousel=e.matches; if(!N()) return;
    placeDots();
    const i=Math.max(0,cur); cur=-1; if(carrousel) set(i); else onScroll();
  });

  // tailles : la taille choisie reste la même d'une pièce à l'autre (si elle y est disponible)
  sizes.addEventListener("click",e=>{
    const b=e.target.closest("button"); if(!b || b.disabled) return;
    geste();
    taille=b.dataset.t; majStock();
  });
  ajout.addEventListener("click",()=>{
    const p=PIECES[cur]; if(!p) return;
    if(!taille){
      sizes.classList.remove("is-shake"); void sizes.offsetWidth; sizes.classList.add("is-shake");
      ajout.textContent="Choisis ta taille"; clearTimeout(t0); t0=setTimeout(majStock,1400);
      sizes.querySelector("button:not(:disabled)")?.focus(); return;
    }
    if(!panier.ajoute(p.id,taille)){
      ajout.textContent=`Plus de stock en ${taille}`; clearTimeout(t0); t0=setTimeout(majStock,1600);
      tiroir.ouvre("panier"); return;
    }
    live.textContent=`${p.nom} ${p.couleur}, taille ${taille}, ajouté au panier.`;
    ajout.textContent="Ajouté ✓"; ajout.classList.add("is-done");
    nb.classList.remove("animate-bump"); void nb.offsetWidth; nb.classList.add("animate-bump");
    clearTimeout(t0); t0=setTimeout(()=>{ ajout.classList.remove("is-done"); majStock(); },1400);
    tiroir.ouvre("panier");
  });

  let construite=false;
  catalogue.surChange(()=>{ if(!construite){ construite=true; construit(); } else majStock(); });
  return {
    erreur(){ detail.textContent="Impossible de charger la collection. Recharge la page dans un instant."; ajout.disabled=true; },
  };
})();

/* ---------- 4. Tiroir panier : panier → coordonnées → paiement Wave (+ capture) → merci ----------
   La commande est créée en passant au paiement (stock réservé) ; « Modifier mes coordonnées » la met à jour au lieu
   d'en créer une autre. Elle est gardée dans le navigateur jusqu'à l'envoi de la capture. */
const tiroir = (function(){
  const $=s=>dlg.querySelector(s), $$=s=>dlg.querySelectorAll(s);
  const dlg=document.getElementById("tiroir"), form=document.getElementById("pc-form"), err=document.getElementById("pc-erreur"),
        liste=document.getElementById("pc-panier"), pied=document.getElementById("pc-pied"), livraison=document.getElementById("pc-livraison"),
        btn=form.querySelector("[type=submit]"), etapes=$$("[data-etape]"), nav=$$("[data-nav]"),
        fichier=document.getElementById("pc-capture"), apercu=document.getElementById("pc-apercu"), errCapture=document.getElementById("pc-erreur-capture");
  const ORDRE=["panier","infos","paiement","merci"];
  const CLE_CMD="tk-commande", CLE_CLIENT="tk-client";
  const lit=k=>{ try{ return JSON.parse(localStorage.getItem(k)); }catch{ return null; } };
  const ecrit=(k,v)=>{ try{ v==null?localStorage.removeItem(k):localStorage.setItem(k,JSON.stringify(v)); }catch{} };
  const signature=()=>JSON.stringify(panier.lignes.map(l=>[l.piece,l.taille,l.quantite]));
  let commande=lit(CLE_CMD); // {reference, jeton, total, lignes, adresse, telephone, signature}

  function etape(nom){
    etapes.forEach(e=>e.hidden=e.dataset.etape!==nom);
    const i=ORDRE.indexOf(nom);
    nav.forEach((li,k)=>{ li.classList.toggle("text-nuit/35",k>i); li.toggleAttribute("aria-current",k===i); });
    if(nom==="paiement") rendPaiement();
    const cible=dlg.querySelector(`[data-etape="${nom}"]`);
    (nom==="infos" ? form.nom : nom==="merci" ? cible : $("[data-ferme]")).focus({preventScroll:true});
  }
  /** rouvrir le panier avec une commande en attente de paiement (même panier) : directement l'étape paiement */
  function ouvre(nom){
    if(!dlg.open) dlg.showModal();
    etape(nom || (commande && panier.lignes.length && commande.signature===signature() ? "paiement" : "panier"));
  }
  function ferme(){ dlg.close(); }
  dlg.addEventListener("close",()=>{ if(!$('[data-etape="merci"]').hidden) etape("panier"); });
  dlg.addEventListener("click",e=>{ if(e.target===dlg) ferme(); }); // clic sur le fond assombri
  dlg.addEventListener("click",e=>{
    const b=e.target.closest("[data-ferme],[data-vers]"); if(!b) return;
    if(b.hasAttribute("data-ferme")) ferme(); else etape(b.dataset.vers);
  });
  document.querySelectorAll("[data-ouvre-panier]").forEach(b=>b.addEventListener("click",()=>ouvre()));

  /* ----- Étape 1 : panier ----- */
  function rendu(){
    const vide=!panier.lignes.length;
    pied.hidden=livraison.hidden=vide;
    $$("[data-total]").forEach(t=>t.textContent=fcfa(panier.total));
    if(vide && !form.hidden) etape("panier");
    liste.innerHTML=vide
      ? `<li class="grid place-items-center gap-4 rounded-xl border-2 border-dashed border-nuit/20 px-6 py-14 text-center">
           <p class="font-display text-3xl uppercase">Ton panier est vide</p>
           <button type="button" data-ferme class="text-sm font-bold tracking-[.14em] uppercase underline underline-offset-4">Voir la collection</button>
         </li>`
      : panier.lignes.map((l,i)=>{
          const p=piece(l.piece); if(!p) return ""; // catalogue pas encore chargé
          const reste=stockDe(p,l.taille), max=panier.max(l.piece,l.taille);
          const alerte = reste<l.quantite
            ? `<p class="mt-1 text-sm font-bold text-rouge">${reste?`Plus que ${reste} disponible${reste>1?"s":""}`:"Épuisé dans cette taille"}</p>` : "";
          return `<li class="flex items-center gap-4 border-b border-nuit/15 py-5 first:pt-0">
            <img src="${p.face}" alt="" class="size-[84px] shrink-0 rounded-lg bg-terre object-contain p-2">
            <div class="min-w-0 flex-1">
              <p class="text-lg leading-tight font-bold">${p.type} ${p.nom}</p>
              <p class="mt-0.5 text-[15px] text-nuit/70">${p.couleur} · Taille ${l.taille}</p>
              ${alerte}
              <div class="mt-2.5 inline-flex h-11 items-center rounded-full border-2 border-nuit">
                <button type="button" data-i="${i}" data-d="-1" class="grid h-full w-10 place-items-center text-xl disabled:opacity-25" aria-label="Retirer un" ${l.quantite<=1?"disabled":""}>−</button>
                <span class="w-6 text-center text-lg font-bold tabular-nums">${l.quantite}</span>
                <button type="button" data-i="${i}" data-d="1" class="grid h-full w-10 place-items-center text-xl disabled:opacity-25" aria-label="Ajouter un" ${l.quantite>=max?"disabled":""}>+</button>
              </div>
            </div>
            <div class="grid shrink-0 justify-items-end gap-2">
              <span class="text-lg font-bold whitespace-nowrap tabular-nums">${fcfa(l.quantite*p.prix)}</span>
              <button type="button" data-i="${i}" data-x class="text-sm text-nuit/70 underline underline-offset-2 hover:text-nuit">Retirer</button>
            </div>
          </li>`;
        }).join("");
  }
  panier.surChange(rendu);
  catalogue.surChange(rendu); // le stock a bougé : alertes et boutons « + » à jour
  liste.addEventListener("click",e=>{
    const b=e.target.closest("button[data-i]"); if(!b) return;
    if(b.hasAttribute("data-x")) panier.retire(+b.dataset.i); else panier.change(+b.dataset.i,+b.dataset.d);
  });

  /* ----- Étape 2 : coordonnées (gardées dans le navigateur pour la prochaine fois) ----- */
  catalogue.surChange(()=>{
    const sel=form.commune; if(sel.options.length>1) return;
    sel.insertAdjacentHTML("beforeend",COMMUNES.map(c=>`<option>${c}</option>`).join(""));
    const client=lit(CLE_CLIENT)||{};
    for(const k of ["nom","telephone","email","quartier","commune","note_client"]) if(client[k] && form[k]) form[k].value=client[k];
  });
  const montre=msg=>{ err.textContent=msg; err.classList.toggle("hidden",!msg); };
  form.addEventListener("submit",async e=>{
    e.preventDefault(); montre("");
    if(!panier.lignes.length) return etape("panier");
    if(!form.checkValidity()){
      const bad=form.querySelector(":invalid");
      montre("Vérifie : "+(bad.closest("label")?.querySelector("span")?.textContent.trim().toLowerCase()||bad.name)+".");
      bad.focus(); return;
    }
    const data=Object.fromEntries(new FormData(form));
    const {website,...client}=data; ecrit(CLE_CLIENT,client);
    data.articles=panier.lignes;
    btn.disabled=true;
    try{
      let res;
      if(commande){ // « Modifier mes coordonnées » ou panier modifié : on met à jour la même commande
        res=await envoie(`/api/precommandes/${commande.reference}`,"PUT",data);
        if(res.status===404 || (res.status===409 && res.json.erreur?.startsWith("Cette commande"))){ commande=null; ecrit(CLE_CMD,null); res=null; }
      }
      if(!res) res=await envoie("/api/precommandes","POST",data);
      if(!res.ok){
        if(res.status===409) catalogue.charge().catch(()=>{}); // stock insuffisant : on montre ce qui reste
        throw new Error(res.json.erreur||"Le serveur n'a pas pu enregistrer ta commande.");
      }
      commande={...commande,...res.json,signature:signature()};
      ecrit(CLE_CMD,commande);
      catalogue.charge().catch(()=>{});
      etape("paiement");
    }catch(x){
      montre(x instanceof TypeError?"Connexion impossible. Réessaie dans un instant.":x.message);
    }finally{ btn.disabled=false; }
  });
  async function envoie(url,method,data){
    const res=await fetch(url,{method,headers:{"Content-Type":"application/json",Accept:"application/json",...(commande?{"X-Jeton":commande.jeton}:{})},body:JSON.stringify(data)});
    return {ok:res.ok,status:res.status,json:await res.json().catch(()=>({}))};
  }

  /* ----- Étape 3 : paiement Wave ----- */
  const chiffres=s=>(s||"").replace(/\D/g,"");
  function rendPaiement(){
    if(!commande) return etape("panier");
    $$("[data-ref]").forEach(e=>e.textContent=commande.reference);
    $$("[data-montant]").forEach(e=>e.textContent=fcfa(commande.total));
    $$("[data-wave]").forEach(e=>e.textContent=PAIEMENT.wave||"");
    $$("[data-tel]").forEach(e=>e.textContent=commande.telephone);
    document.getElementById("pc-recap").innerHTML=
      commande.lignes.map(l=>`<p>${l.quantite} × ${l.libelle} (${l.taille})</p>`).join("")+`<p>Livraison Yango à ${commande.adresse}</p>`;
    const lien=document.getElementById("pc-lien-wave");
    lien.hidden=!PAIEMENT.lien; if(PAIEMENT.lien) lien.href=PAIEMENT.lien.replace("{montant}",commande.total);
    const msg=`Bonjour ! Voici la capture de mon paiement Wave de ${fcfa(commande.total)} pour la commande ${commande.reference}.`;
    document.getElementById("pc-whatsapp").href=`https://wa.me/${chiffres(PAIEMENT.whatsapp)}?text=${encodeURIComponent(msg)}`;
    apercu.hidden=true; errCapture.classList.add("hidden");
  }
  document.getElementById("pc-copier").addEventListener("click",async e=>{
    const b=e.currentTarget, num=(PAIEMENT.wave||"").replace(/\s/g,"");
    try{ await navigator.clipboard.writeText(num); b.textContent="Copié ✓"; }
    catch{ b.textContent=num; }
    setTimeout(()=>b.textContent="Copier",1800);
  });

  /** réduit la capture (souvent un PNG de plusieurs Mo) en JPEG ~300 Ko : envoi rapide en 4G */
  async function allege(f){
    if(!/^image\/(png|jpe?g|webp)$/.test(f.type)) return f;
    try{
      const img=await createImageBitmap(f), k=Math.min(1,1800/Math.max(img.width,img.height));
      const c=document.createElement("canvas"); c.width=Math.round(img.width*k); c.height=Math.round(img.height*k);
      c.getContext("2d").drawImage(img,0,0,c.width,c.height);
      const blob=await new Promise(r=>c.toBlob(r,"image/jpeg",.85));
      return blob && blob.size<f.size ? new File([blob],"capture.jpg",{type:"image/jpeg"}) : f;
    }catch{ return f; }
  }
  fichier.addEventListener("change",async()=>{
    const f=fichier.files[0]; if(!f || !commande) return;
    errCapture.classList.add("hidden");
    apercu.hidden=false; apercu.querySelector("img").src=URL.createObjectURL(f); apercu.querySelector("[data-etat]").textContent="Envoi de ta capture…";
    fichier.disabled=true;
    try{
      const corps=new FormData(); corps.append("capture",await allege(f));
      const res=await fetch(`/api/precommandes/${commande.reference}/capture`,{method:"POST",headers:{Accept:"application/json","X-Jeton":commande.jeton},body:corps});
      const json=await res.json().catch(()=>({}));
      if(!res.ok) throw new Error(json.erreur||(res.status===413?"Image trop lourde.":"L'envoi a échoué. Réessaie."));
      etape("merci"); // les infos de la commande restent affichées sur l'écran merci
      commande=null; ecrit(CLE_CMD,null); panier.vide(); catalogue.charge().catch(()=>{});
    }catch(x){
      apercu.hidden=true;
      errCapture.textContent=x instanceof TypeError?"Connexion impossible. Réessaie dans un instant.":x.message; errCapture.classList.remove("hidden");
    }finally{ fichier.disabled=false; fichier.value=""; }
  });

  return {ouvre};
})();

catalogue.charge().catch(()=>collection.erreur());

/* ---------- 3. Mur de télés ---------- */
(function tvs(){
  // bruit statique généré une fois
  const c=document.createElement("canvas"); c.width=c.height=160; const x=c.getContext("2d"), d=x.createImageData(160,160);
  for(let i=0;i<d.data.length;i+=4){const v=Math.random()*255|0; d.data[i]=d.data[i+1]=d.data[i+2]=v; d.data[i+3]=255;}
  x.putImageData(d,0,0); document.documentElement.style.setProperty("--noise",`url(${c.toDataURL()})`);

  const wall=document.getElementById("wall"), N=9, withPanel=[0,2,5,7], withAnt=[1,3];
  let k=0; const next=()=>CLIP_SOURCES[(k++)%CLIP_SOURCES.length];
  const tvs=[];
  for(let i=0;i<N;i++){
    const tv=document.createElement("div"); tv.className="tv"+(withPanel.includes(i)?" tv--side":"");
    const vid = i===2 ? TV_VIDEOS[0] : i===6 ? TV_VIDEOS[1] : null;
    const media = vid ? `<video src="${vid}" poster="${vid.replace(".mp4",".jpg")}" autoplay muted loop playsinline></video>` : `<img src="${next()}" alt="" loading="lazy">`;
    tv.innerHTML = (withAnt.includes(i)?'<span class="tv__ant"></span>':'') +
      `<div class="tv__screen">${media}<span class="tv__static"></span><span class="tv__osd">CH ${String(i+2).padStart(2,"0")}</span></div>` +
      (withPanel.includes(i)?'<div class="tv__panel"><span class="tv__knob"></span><span class="tv__knob"></span><span class="tv__grill"></span></div>':'');
    wall.appendChild(tv); tvs.push(tv);
  }
  if(reduce) return;
  // zapping aléatoire
  setInterval(()=>{
    const tv=tvs[Math.random()*N|0], img=tv.querySelector("img"); if(!img) return;
    tv.classList.add("is-switching");
    setTimeout(()=>{ img.src=next(); const o=tv.querySelector(".tv__osd"); o.textContent="CH "+String(Math.random()*90+10|0); },160);
    setTimeout(()=>tv.classList.remove("is-switching"),340);
  },650);
})();

/* ---------- Pellicule : les mots avancent image par image, sans jamais s'arrêter ---------- */
(function film(){
  const t=document.getElementById("film");
  const words=[...FILM_WORDS,...FILM_WORDS];
  t.innerHTML=words.map(w=>`<div class="film__frame${RED.has(w)?" is-red":OCRE.has(w)?" is-ocre":""}">${w}</div>`).join("");
  if(reduce) return;
  setInterval(()=>{
    const f=t.firstElementChild, step=f.getBoundingClientRect().width;
    t.style.transition="transform .22s cubic-bezier(.7,0,.3,1)"; t.style.transform=`translateX(${-step}px)`;
    setTimeout(()=>{ t.style.transition="none"; t.appendChild(f); t.style.transform="translateX(0)"; },240);
  },520);
})();
