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
});}

// EDIT USER INFO via UI sur DASH_ACCOUNT.PHP pour l'instant

function editLeUser() {
    const usernameCell = document.querySelector(".usernameValue").textContent;
    const emailCell = document.querySelector(".emailValue").textContent;
    let roleCell = '';

    if (document.querySelector('.admin')) {
        roleCell = 'admin';
    }
    if (document.querySelector('.employee')) {
        roleCell = 'employee';
    }

    console.log('roleCell -> ' + roleCell);


    document.querySelectorAll('tr td:not(:last-child)').forEach(td => {
        if (td.querySelector('div[contenteditable="true"]')) return;
        if (td.classList.value === 'admin' || td.classList.value === 'employee') {
            let select = document.createElement('select');
            let optionEmployee = document.createElement('option');
            optionEmployee.value = 'employee';
            optionEmployee.text = 'employee';
            let optionAdmin = document.createElement('option');
            optionAdmin.value = 'admin';
            optionAdmin.text = 'admin';

            select.appendChild(optionEmployee);
            select.appendChild(optionAdmin);
            td.innerHTML = '';
            td.appendChild(select);

            // On sélectionne la bonne option dans le select :
            if (td.classList.value === 'admin') {
                select.value = 'admin';
            } else if (td.classList.value === 'employee') {
                select.value = 'employee';
            };

        } else {
            let div = document.createElement('div');
            div.setAttribute('contenteditable', 'true');
            div.setAttribute('data-text', 'Votre placeholder');
            div.setAttribute('style', 'min-width: 100px; min-height: 20px; border: 1px solid #ccc; padding: 5px;');
            div.classList.add(td.classList.value); // On conserve la classe (usernameValue ou emailValue)
            div.classList.add('editable-div');

            // On conserve l'ancien contenu si besoin :
            div.innerText = td.innerText.trim();
            
            td.innerHTML = '';
            td.appendChild(div);

        };

    document.querySelectorAll('tr td:last-child').forEach(td => {
        let btnSave = document.createElement('button');
        btnSave.textContent = 'Enregistrer';
        btnSave.classList.add('save-btn');

        let btnCancel = document.createElement('button');
        btnCancel.textContent = 'Annuler';
        btnCancel.classList.add('cancel-btn');

        td.innerHTML = '';
        td.appendChild(btnSave);
        td.appendChild(btnCancel);

        btnCancel.addEventListener('click', () => {
            document.querySelector('.usernameValue').textContent = usernameCell;
            document.querySelector('.emailValue').textContent = emailCell;
            document.querySelector('.admin').textContent = roleCell;
            td.innerHTML = '<button class="editBtn" type="button" onclick="editLeUser()">Gérer</button>';
        });

        // Enregistrement des modifications : cette partie n'est toujours pas fonctionnelle côté backend, le front me parait good 
        btnSave.addEventListener('click', () => {
            const newUsername = document.querySelector('.usernameValue div').innerText.trim();
            const newEmail = document.querySelector('.emailValue div').innerText.trim();
            const newRole = document.querySelector('.admin select, .employee select').value;

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '/dashboard/users/update';

            form.innerHTML = `
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="username" value="${newUsername}">
                <input type="hidden" name="email" value="${newEmail}">
                <input type="hidden" name="role" value="${newRole}">
            `;

            document.body.appendChild(form);
            form.submit();
        });

    });

})};




// UPDATE USER INFO VIA UI (EN TEST)
document.querySelectorAll(".edit-btn").forEach((button) => {
    button.addEventListener("click", (e) => {
        const row = e.target.closest("tr");
        const userId = row.dataset.userId;
        const usernameCell = row.querySelector(".username");
        const emailCell = row.querySelector(".email");
        const roleCell = row.querySelector(".role");
        const btn = row.querySelector(".edit-btn");

        if (btn.textContent === "Gérer") {
            // Passer en mode édition
            const username = usernameCell.textContent.trim();
            const email = emailCell.textContent.trim();
            const role = roleCell.textContent.trim();

            usernameCell.innerHTML = `<input type="text" name="username" value="${username}">`;
            emailCell.innerHTML = `<input type="email" name="email" value="${email}">`;
            roleCell.innerHTML = `
                <select name="role">
                    <option value="employee" ${
                        role === "employee" ? "selected" : ""
                    }>employee</option>
                    <option value="admin" ${
                        role === "admin" ? "selected" : ""
                    }>admin</option>
                </select>
            `;

            btn.textContent = "Enregistrer";

            if (!row.querySelector(".cancel-btn")) {
                const cancelBtn = document.createElement("button");
                cancelBtn.textContent = "Annuler";
                cancelBtn.type = "button";
                cancelBtn.classList.add("cancel-btn");
                btn.insertAdjacentElement("afterend", cancelBtn);

                cancelBtn.addEventListener("click", () => {
                    usernameCell.textContent = username;
                    emailCell.textContent = email;
                    roleCell.textContent = role;
                    roleCell.className = `${role}`;

                    btn.textContent = "Gérer";
                    cancelBtn.remove();
                });
            }
        } else {
            const newUsername = row.querySelector(
                'input[name="username"]'
            ).value;
            const newEmail = row.querySelector('input[name="email"]').value;
            const newRole = row.querySelector('select[name="role"]').value;

            const form = document.createElement("form");
            form.method = "POST";
            form.action = "/dashboard/users/update";

            form.innerHTML = `
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" value="${userId}">
                <input type="hidden" name="username" value="${newUsername}">
                <input type="hidden" name="email" value="${newEmail}">
                <input type="hidden" name="role" value="${newRole}">
            `;

            document.body.appendChild(form);
            form.submit();
        }
    });
});
