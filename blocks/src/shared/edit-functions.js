export function changeHostName(oldUrl) {
    // Create a new URL object with the old URL
    let url = new URL(oldUrl);

    // Update the protocol with the current page's protocol
    url.protocol = window.location.protocol;

    // Update the host with the current page's hostname
    url.host = window.location.hostname;

    // Return the new URL
    return url.href;
}

export function closeModal(ref) {
    if (ref.current) {
        ref.current.classList.remove('modal-open');
    }
}

export function getMyClubSections(setPosts, selectPostLabel) {
    const {apiFetch} = wp;

    apiFetch({path: '/myclub/sections/v1/sections'}).then(
        fetchedItems => {
            const postOptions = fetchedItems.map(post => ({
                label: post.title,
                value: post.id
            }));

            postOptions.unshift(selectPostLabel);

            setPosts(postOptions);
        }
    );
}
