const issueModal = document.getElementById('issue-modal');
const modalTitle = document.getElementById('modal-title');
const issueTypeSelect = document.getElementById('issue_type');
const roomNumberInput = document.getElementById('room_number'); // New line
const openModalButtons = document.querySelectorAll('.action-btn');
const closeModalButton = document.getElementById('close-modal');

const openModalButton = document.querySelector('.file-issue-btn');

if (openModalButton) {
    openModalButton.addEventListener('click', () => {
        modalTitle.textContent = 'New Issue';
        issueModal.style.display = 'flex';
        
        // Fetch room number via AJAX
        fetch('get_room_number.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    roomNumberInput.value = data.room_number;
                } else {
                    roomNumberInput.value = 'N/A'; // Or handle error appropriately
                    console.error(data.message);
                }
            })
            .catch(error => {
                console.error('Error fetching room number:', error);
                roomNumberInput.value = 'Error';
            });
    });
}

function closeModal() {
    issueModal.style.display = 'none';
}

closeModalButton.addEventListener('click', closeModal);

issueModal.addEventListener('click', (event) => {
    if (event.target === issueModal) {
        closeModal();
    }
});
