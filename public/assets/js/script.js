const header = document.getElementById("header");
const hamburger = document.querySelector(".hamburger");
const navbar = document.querySelector(".navbar");
const downloadLink = document.getElementById("download");
const changePassword = document.getElementById("submit-update-password");
const checkboxes = document.querySelectorAll(".user-checkbox");
const deleteBtn = document.querySelector(".deleteUser");
const createUser = document.getElementById("createUserBtn");
const overlay = document.getElementById("image-overlay");
const overlayImg = document.getElementById("overlay-img");
const closeBtn = document.getElementById("close-overlay");
const prevBtn = document.getElementById("prev-img");
const nextBtn = document.getElementById("next-img");

// MASQUER LE HEADER AU SCROLL
let lastScroll = window.scrollY;

window.addEventListener("scroll", () => {
    if (window.scrollY > lastScroll && window.scrollY > 20) {
        header.classList.add("hidden");
    } else {
        header.classList.remove("hidden");
    }
    lastScroll = window.scrollY;
});

// FILTRE DES ACTUALITES
const filterButtons = document.querySelectorAll(".filter-btn");
const articles = document.querySelectorAll(".news-item");

filterButtons.forEach((button) => {
    button.addEventListener("click", () => {
        const filter = button.dataset.filter;
        filterButtons.forEach((btn) => btn.classList.remove("active-filter"));
        button.classList.add("active-filter");

        articles.forEach((article) => {
            const category = article.dataset.category;

            if (filter === "all" || category === filter) {
                article.style.display = "flex";
            } else {
                article.style.display = "none";
            }
        });
    });
});

// OUVERTURE DU MENU MOBILE
if (hamburger && navbar) {
    hamburger.addEventListener("click", () => {
        navbar.classList.toggle("visible");
        hamburger.classList.toggle("open");
    });
}
const galleryImages = document.querySelectorAll(".gallery-grid img");
const totalImages = galleryImages.length;

let currentImgIndex = -1;

function updateOverlayImage(index) {
    if (totalImages === 0) return;
    currentImgIndex = (index + totalImages) % totalImages;
    overlayImg.src = galleryImages[currentImgIndex].src;
    overlayImg.alt =
        galleryImages[currentImgIndex].alt || "Image de la galerie"; // accessibilité
}

function showOverlay(index) {
    updateOverlayImage(index);
    overlay.classList.add("active");
    overlay.setAttribute("aria-hidden", "false");
    overlay.focus();
}

function closeOverlay() {
    overlay.classList.remove("active");
    overlay.setAttribute("aria-hidden", "true");
    overlayImg.src = "";
    overlayImg.alt = "";
    currentImgIndex = -1;
}

function showPrev() {
    updateOverlayImage(currentImgIndex - 1);
}
function showNext() {
    updateOverlayImage(currentImgIndex + 1);
}

prevBtn?.addEventListener("click", showPrev);
nextBtn?.addEventListener("click", showNext);

galleryImages.forEach((img, idx) =>
    img.addEventListener("click", () => showOverlay(idx))
);

overlay?.addEventListener("click", (e) => {
    if (e.target === overlay || e.target === closeBtn) closeOverlay();
});

overlay?.addEventListener("keydown", (e) => {
    if (!overlay.classList.contains("active")) return;

    switch (e.key) {
        case "ArrowRight":
            showNext();
            break;
        case "ArrowLeft":
            showPrev();
            break;
        case "Escape":
            closeOverlay();
            break;
    }
});

// TELECHARGEMENT DE FICHIER
const handleDownload = () => {
    const fileUrl = "/assets/files/dossier_de_candidature.pdf";
    const link = document.createElement("a");
    link.href = fileUrl;
    link.download = "dossier_de_candidature.pdf";
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
};

if (downloadLink) {
    downloadLink.addEventListener("click", handleDownload);
}

// RESPONSIVE
const showMobileNav = () => {
    if (!navbar || !hamburger) return;
    if (window.innerWidth <= 950) {
        navbar.classList.add("mobileNav");
        hamburger.style.display = "flex";
    } else {
        hamburger.style.display = "none";
        navbar.classList.remove("mobileNav");
        hamburger.classList.remove("open");
    }
};

window.addEventListener("resize", showMobileNav);
window.addEventListener("load", showMobileNav);

// USER CHECKBOXES

function toggleDeleteBtn() {
    const isChecked = Array.from(checkboxes).some((cb) => cb.checked);
    deleteBtn.disabled = !isChecked;
}
checkboxes.forEach((cb) => cb.addEventListener("change", toggleDeleteBtn));

// CREER UN USER (MODALE)
if (createUser) {
    createUser.addEventListener("click", () => {
        const popup = document.querySelector(".popup");
        popup.classList.remove("hidden");

        popup.addEventListener("click", (e) => {
            if (e.target === popup) {
                popup.classList.add("hidden");
            }
        });
    });
}



// CREER EVENT AGENDA
document.getElementById("btn-open-modal").onclick = function() {
    document.getElementById("modalCreateEvent").style.display = "flex";
};

document.getElementById("closeModal").onclick = function() {
    document.getElementById("modalCreateEvent").style.display = "none";
};

// VOIR EVENT AGENDA
document.querySelectorAll('#event-container').forEach(container => {
    container.onclick = function() {
        const detailDiv = this.querySelector('.detail');
        if (detailDiv) {
            detailDiv.hidden = !detailDiv.hidden;
        }
    };
}); 

// EDIT USER INFO via UI sur DASH_ACCOUNT.PHP pour l'instant

function editLeUser(event) {
    console.log("Bouton 'Gérer' cliqué");
    // Récupérer la ligne (tr) correspondant au bouton "Gérer" cliqué
    const row = event.target.closest('tr');
    if (!row) return; // Sécurité si la ligne n'est pas trouvée

    // Récupérer les cellules spécifiques de cette ligne
    const usernameCell = row.querySelector('.usernameValue');
    const emailCell = row.querySelector('.emailValue');
    let roleCell = row.querySelector('.admin, .employee');
    const actionCell = row.querySelector('td:last-child');
    const id = row.querySelector('.idValue').textContent.trim(); // Récupérer l'ID depuis la cellule cachée
    console.log("ID de l'utilisateur :", id);

    // Stocker les valeurs initiales pour pouvoir les restaurer en cas d'annulation
    const originalUsername = usernameCell.textContent.trim();
    const originalEmail = emailCell.textContent.trim();
    const originalRole = roleCell.classList.contains('admin') ? 'admin' : 'employee';

    // Remplacer le contenu des cellules par des inputs/select
    if (!usernameCell.querySelector('div[contenteditable="true"]')) {
        // Username
        const usernameDiv = document.createElement('div');
        usernameDiv.setAttribute('contenteditable', 'true');
        usernameDiv.setAttribute('style', 'min-width: 100px; min-height: 20px; border: 1px solid #ccc; padding: 5px;');
        usernameDiv.textContent = originalUsername;
        usernameCell.innerHTML = '';
        usernameCell.appendChild(usernameDiv);

        // Email
        const emailDiv = document.createElement('div');
        emailDiv.setAttribute('contenteditable', 'true');
        emailDiv.setAttribute('style', 'min-width: 100px; min-height: 20px; border: 1px solid #ccc; padding: 5px;');
        emailDiv.textContent = originalEmail;
        emailCell.innerHTML = '';
        emailCell.appendChild(emailDiv);

        // Role
        const select = document.createElement('select');
        const optionEmployee = document.createElement('option');
        optionEmployee.value = 'employee';
        optionEmployee.text = 'employee';
        const optionAdmin = document.createElement('option');
        optionAdmin.value = 'admin';
        optionAdmin.text = 'admin';
        select.appendChild(optionEmployee);
        select.appendChild(optionAdmin);
        select.value = originalRole;
        roleCell.innerHTML = '';
        roleCell.appendChild(select);

        // Remplacer le bouton "Gérer" par "Enregistrer", "Annuler" et "Supprimer"
        actionCell.innerHTML = '';
        const btnSave = document.createElement('button');
        btnSave.textContent = 'Enregistrer';
        btnSave.classList.add('save-btn');

        const btnCancel = document.createElement('button');
        btnCancel.textContent = 'Annuler';
        btnCancel.classList.add('cancel-btn');

        const btnSuppr = document.createElement('button');
        btnSuppr.textContent = 'Supprimer';
        btnSuppr.classList.add('suppr-btn');


        actionCell.appendChild(btnSave);
        actionCell.appendChild(btnCancel);
        actionCell.appendChild(btnSuppr);
        

        // Gestion du bouton "Supprimer"
        btnSuppr.addEventListener('click', () => {
            if (confirm("Êtes-vous sûr de vouloir supprimer cet utilisateur ?")) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '/dashboard/users/delete';

                form.innerHTML = `
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="user_ids[]" value="${id}">
                    <?= \CapsuleLib\Security\CsrfTokenManager::insertInput(); ?>
                `;

                document.body.appendChild(form);
                form.submit();
            }
        });


        

        // Gestion du bouton "Annuler"
        btnCancel.addEventListener('click', () => {
            usernameCell.textContent = originalUsername;
            emailCell.textContent = originalEmail;
            roleCell.className = originalRole + ' role'; // Restaurer la classe du rôle
            roleCell.innerHTML = "<p> '<?php echo htmlspecialchars($user->email); ?>' </p>";
            row.querySelector('.role p').textContent = originalRole;
            actionCell.innerHTML = '<button class="editBtn" type="button" onclick="editLeUser(event)">Gérer</button>';
        });

        // Gestion du bouton "Enregistrer"
        btnSave.addEventListener('click', () => {
            if (confirm('Enregistrer les modifications ?')){
                const newUsername = usernameCell.querySelector('div').textContent.trim();
                const newEmail = emailCell.querySelector('div').textContent.trim();
                const newRole = select.value;
    
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '/dashboard/users/update';
    
                form.innerHTML = `
                    
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" value="${id}">
                    <input type="hidden" name="username" value="${newUsername}">
                    <input type="hidden" name="email" value="${newEmail}">
                    <input type="hidden" name="role" value="${newRole}">
                `;
    
                document.body.appendChild(form);
                form.submit();
            }
        });
    }
}

// function suppUsers() {
//     const selectedIds = Array.from(checkboxes)
//         .filter(cb => cb.checked)
//         .map(cb => cb.value);

//     if (selectedIds.length === 0) return;

//     const form = document.createElement("form");
//     form.method = "POST";
//     form.action = "/dashboard/users/delete";

//     form.innerHTML = "<?= \CapsuleLib\Security\CsrfTokenManager::insertInput(); ?>"


//     selectedIds.forEach(id => {
//         const input = document.createElement("input");
//         input.type = "hidden";
//         input.name = "user_ids[]";
//         input.value = id;
//         form.appendChild(input);
//     });

//     document.body.appendChild(form);
//     form.submit();
// }