/* Back office : petits comportements, sans dépendance */

// boutons de confirmation (annuler, supprimer)
document.addEventListener("click", e => {
  const b = e.target.closest("[data-confirme]");
  if (b && !confirm(b.dataset.confirme)) e.preventDefault();
});

// liste des précommandes : changer le statut dans le menu l'enregistre directement
document.querySelectorAll("[data-auto-submit]").forEach(s => s.addEventListener("change", () => {
  if (s.value === "annulee" && !confirm("Annuler cette précommande ? Les pièces seront remises en stock.")) {
    s.value = [...s.options].find(o => o.defaultSelected)?.value; return;
  }
  s.form.requestSubmit();
}));

// fiche pièce : aperçu immédiat de la photo choisie
document.querySelectorAll("[data-photo]").forEach(input => input.addEventListener("change", () => {
  const f = input.files[0], img = document.querySelector(`[data-apercu="${input.dataset.photo}"]`);
  if (!f || !img) return;
  img.src = URL.createObjectURL(f); img.classList.remove("hidden");
  img.nextElementSibling?.classList.remove("opacity-100!");
}));

// nouvelle pièce : l'identifiant se remplit tout seul à partir du nom et de la couleur
const form = document.querySelector("[data-piece-form][data-nouvelle]");
if (form) {
  const slug = form.querySelector("[data-slug]");
  let touche = !!slug.value;
  slug.addEventListener("input", () => touche = true);
  form.querySelectorAll("[data-slug-source]").forEach(i => i.addEventListener("input", () => {
    if (touche) return;
    slug.value = [form.nom.value, form.couleur.value].join(" ")
      .normalize("NFD").replace(/[̀-ͯ]/g, "").toLowerCase().replace(/[^a-z0-9]+/g, "-").replace(/^-|-$/g, "");
  }));
}

// le message « ✓ Enregistré » disparaît tout seul
const flash = document.querySelector("[data-flash]");
if (flash) setTimeout(() => { flash.style.transition = "opacity .4s"; flash.style.opacity = "0"; setTimeout(() => flash.remove(), 400); }, 4000);
