function closeButtonListener() {
    const modal = document.getElementsByClassName('modal-open')[0];
    const close = modal.getElementsByClassName('close')[0];

    modal.classList.remove('modal-open');
    close.removeEventListener('click', closeButtonListener);
    modal.removeEventListener('click', closeModalListener);
}

function closeModalListener() {
    const modal = document.getElementsByClassName('modal-open')[0];
    const close = modal.getElementsByClassName('close')[0];

    modal.classList.remove('modal-open');
    close.removeEventListener('click', closeButtonListener);
    modal.removeEventListener('click', closeModalListener);
}

function showModal(modalClassName, labels, data) {
    const modal = document.getElementsByClassName(modalClassName)[0];
    const image = modal.getElementsByClassName('image')[0];
    const information = modal.getElementsByClassName('information')[0];
    const close = modal.getElementsByClassName('close')[0];
    if (data.image_id) {
        image.innerHTML = '<img src="' + data.image_url + '" alt="' + data.name.replaceAll('u0022', '\"') + '" />';
    }
    let output = '<div class="name">' + data.name.replaceAll('u0022', '\"') + '</div>';

    if (data.role || data.phone || data.email || data.age || (data.dynamic_fields && data.dynamic_fields.length)) {
        output += '<table>';

        if (data.role) {
            output += `<tr><th>${labels.role}</th><td>${data.role.replaceAll('u0022', '\"')}</td></tr>`;
        }

        if (data.age) {
            output += `<tr><th>${labels.age}</th><td>${data.age}</td></tr>`;
        }

        if (data.email) {
            output += `<tr><th>${labels.email}</th><td><a href="mailto:${data.email}">${data.email}</a></td></tr>`;
        }

        if (data.phone) {
            output += `<tr><th>${labels.phone}</th><td><a href="tel:${data.phone}">${data.phone}</a></td></tr>`;
        }

        if (data.dynamic_fields && data.dynamic_fields.length) {
            data.dynamic_fields.forEach((field) => {
                output += `<tr><th>${field.name}</th><td>${field.value.replaceAll('u0022', '\"')}</td></tr>`;
            });
        }

        output += '</table>';
    }

    information.innerHTML = output;

    modal.classList.add('modal-open');
    modal.addEventListener('click', closeModalListener);

    close.addEventListener('click', closeButtonListener);
}
