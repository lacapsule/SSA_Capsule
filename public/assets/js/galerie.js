// Galerie et visionneuse, version JavaScript sans compilation (socles HTML et PHP), équivalente à
// source/scripts/galerie.ts et visionneuse.ts. La visionneuse n'est préparée qu'au premier clic.
(function () {
  "use strict";
  var visionneuses = new WeakMap();

  function preparer(galerie, dialog) {
    var liens = Array.prototype.slice.call(galerie.querySelectorAll("a[data-index]"));
    var img = document.createElement("img");
    img.decoding = "async";
    dialog.querySelector(".visionneuse__scene").prepend(img);
    var legende = dialog.querySelector(".visionneuse__scene figcaption");
    var position = dialog.querySelector("[data-position-texte]");
    var courant = 0;

    function afficher(index) {
      courant = (index + liens.length) % liens.length;
      var lien = liens[courant];
      var vignette = lien.querySelector("img");
      img.width = Number(vignette.getAttribute("width"));
      img.height = Number(vignette.getAttribute("height"));
      img.src = vignette.currentSrc || vignette.src;
      img.alt = vignette.alt;
      if (lien.href !== img.src) {
        var affichee = courant;
        var grande = new Image();
        grande.onload = function () { if (courant === affichee) img.src = grande.src; };
        grande.src = lien.href;
      }
      legende.textContent = lien.dataset.legende || "";
      legende.hidden = !lien.dataset.legende;
      position.textContent = (dialog.dataset.position || "{n} / {total}")
        .replace("{n}", String(courant + 1))
        .replace("{total}", String(liens.length));
    }

    dialog.querySelector("[data-fermer]").addEventListener("click", function () { dialog.close(); });
    var prec = dialog.querySelector("[data-precedente]");
    var suiv = dialog.querySelector("[data-suivante]");
    if (prec) prec.addEventListener("click", function () { afficher(courant - 1); });
    if (suiv) suiv.addEventListener("click", function () { afficher(courant + 1); });
    dialog.addEventListener("keydown", function (e) {
      if (liens.length < 2 || (e.key !== "ArrowLeft" && e.key !== "ArrowRight")) return;
      e.preventDefault();
      afficher(courant + (e.key === "ArrowLeft" ? -1 : 1));
    });
    dialog.addEventListener("click", function (e) { if (e.target === dialog) dialog.close(); });
    dialog.addEventListener("close", function () { setTimeout(function () { liens[courant].focus(); }); });
    return afficher;
  }

  function ouvrir(galerie, index) {
    var dialog = galerie.querySelector("dialog.visionneuse");
    var afficher = visionneuses.get(dialog);
    if (!afficher) { afficher = preparer(galerie, dialog); visionneuses.set(dialog, afficher); }
    afficher(index);
    dialog.showModal();
  }

  var dialogueModal = typeof HTMLDialogElement === "function" && "showModal" in HTMLDialogElement.prototype;
  document.querySelectorAll("[data-galerie]:not([data-pret])").forEach(function (g) {
    g.dataset.pret = "";
    g.addEventListener("click", function (e) {
      var a = e.target instanceof Element ? e.target.closest("a[data-index]") : null;
      if (!a || !dialogueModal || e.button !== 0 || e.ctrlKey || e.metaKey || e.shiftKey || e.altKey) return;
      e.preventDefault();
      ouvrir(g, Number(a.dataset.index));
    });
  });
})();
