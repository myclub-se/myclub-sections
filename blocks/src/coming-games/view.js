import {addShowLessListener, addShowMoreListener} from "../shared/shared-functions";

document.addEventListener('DOMContentLoaded', (event) => {
    // Add click functionality to display member modal.
    addShowMoreListener('coming-game');
    addShowLessListener('coming-game');
});
