// public/js/isocel-app.js

document.addEventListener("DOMContentLoaded", function () {
    // Gestion du menu SMS (expansion/fermeture)
    const smsNav = document.getElementById('sms-nav');
    const subNav = smsNav?.nextElementSibling;

    if (smsNav && subNav) {
        smsNav.addEventListener('click', function (e) {
            e.preventDefault();
            const isVisible = subNav.style.display === 'block';
            subNav.style.display = isVisible ? 'none' : 'block';
        });
    }

    // Checkbox : sélectionner tous les contacts
    const selectAll = document.getElementById('select-all');
    const rowCheckboxes = document.querySelectorAll('.row-checkbox');

    if (selectAll) {
        selectAll.addEventListener('change', function () {
            rowCheckboxes.forEach(checkbox => {
                checkbox.checked = selectAll.checked;
            });
        });
    }

    // Fonction de suppression (associée dans Twig via onclick)
    window.deleteContact = function (contactId) {
        if (confirm('Êtes-vous sûr de vouloir supprimer ce contact ?')) {
            const url = document
                .querySelector(`button[onclick*="deleteContact(${contactId})"]`)
                ?.getAttribute('onclick')
                .match(/path\('contacts_delete', \{id: 'CONTACT_ID'\}\)/);

            // Fallback URL (remplacer manuellement dans le twig sinon)
            const deleteUrl = `/contacts/${contactId}`;

            fetch(deleteUrl, {
                method: 'DELETE',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/json',
                },
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Erreur lors de la suppression');
                    }
                })
                .catch(error => {
                    console.error('Erreur :', error);
                    alert('Erreur lors de la suppression');
                });
        }
    };

    // Changement du nombre d'éléments par page
    window.changePageSize = function (limit) {
        const url = new URL(window.location.href);
        url.searchParams.set('limit', limit);
        url.searchParams.delete('page');
        window.location.href = url.toString();
    };

    const smsContactGroupsSelectElement = document.getElementById('sms_contact_groups_select');
    if (smsContactGroupsSelectElement) {
        // Vérifiez que Select2 n'est pas déjà initialisé pour cet élément
        if (typeof $ !== 'undefined' && $.fn.select2 && !$(smsContactGroupsSelectElement).data('select2')) {
            $(smsContactGroupsSelectElement).select2({
                placeholder: 'Sélectionnez un ou plusieurs groupes',
                allowClear: true
            });
        }
    }
});
