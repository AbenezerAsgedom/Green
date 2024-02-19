const selectableCards = document.querySelectorAll('.card.selectable');
const floatingButtonBadge = document.querySelector('.floating-button .badge');
const cartModalBody = document.querySelector('.Items');
let counter = 0;
const selectedCardsData = [];

selectableCards.forEach((card, index) => {
    const button = card.querySelector('.btn');
    card.addEventListener('click', function() {
        this.classList.toggle('selected');
        if (this.classList.contains('selected')) {
            button.textContent = 'Cancel';
            button.classList.remove('btn-outline-success');
            button.classList.add('btn-outline-danger');
            counter++;
            selectedCardsData.push(index);
        } else {
            button.textContent = 'Select';
            button.classList.remove('btn-outline-danger');
            button.classList.add('btn-outline-success');
            counter--;
            const selectedIndex = selectedCardsData.indexOf(index);
            if (selectedIndex > -1) {
                selectedCardsData.splice(selectedIndex, 1);
            }
        }

        // Update the floating button badge
        floatingButtonBadge.textContent = counter;
        if (counter > 0) {
            floatingButtonBadge.style.display = 'block';
        } else {
            floatingButtonBadge.style.display = 'none';
        }
    });
});

// Show selected contents in the cart modal
const cartModal = document.querySelector('#exampleModal');
const cartModalTitle = cartModal.querySelector('.modal-title');

const showSelectedContents = () => {
    if (selectedCardsData.length > 0) {
        cartModalTitle.textContent = 'Cart';

        let cartItemsHTML = '';
        let totalItems = 0;
        let totalPrice = 0;

        selectedCardsData.forEach((index) => {
            const selectedCard = selectableCards[index];
            const cardTitle = selectedCard.querySelector('.text-dark').textContent;
            const cardBodyHTML = selectedCard.querySelector('.card-body').innerHTML;
            const price = parseFloat(selectedCard.dataset.price); // Retrieve the price from the data attribute
            const quantity = 1; // Each selected item has a quantity of 1

            totalItems += quantity;
            totalPrice += quantity * price;
            cartModalBody.classList.add('col-md-6');
            cartModalBody.classList.remove('col-md-12');
            cartItemsHTML += `
              <div class="selected-card shadow card mb-3">
                <div class="row g-0">
                  <div class="col-md-8">
                    <div class="card-body text-start">
                      <h6 class="card-title lead" style='font-size:16px;'>Name: ${cardTitle}</h6>
                      <h6 class="card-title lead" style='font-size:16px;'>Price: $${price}</h6>
                    </div>
                  </div>
                  <div class="col-md-4">
                    <button type="button" class="btn mt-1 me-1 cancel-btn position-absolute top-0 end-0">
                      <i class="fas fa-times text-danger" style='font-size:20px;'></i>
                    </button>
                  </div>
                </div>
              </div>`;
        });

        const cartSummary = document.querySelector('.Summary');
        cartSummary.innerHTML = `
      <div class="card">
        <div class="card-body">
          <h5 class="card-title">Cart Summary</h5>
          <p class="card-text">Total items: ${totalItems}</p>
          <p class="card-text">Total price: $${totalPrice.toFixed(2)}</p>
        </div>
      </div>`;

        cartModalBody.innerHTML = cartItemsHTML;

        const cancelButtons = cartModalBody.querySelectorAll('.cancel-btn');
        cancelButtons.forEach((cancelBtn, index) => {
            cancelBtn.addEventListener('click', () => {
                removeCardFromCart(index);
            });
        });
    } else {
        cartModalTitle.textContent = 'Cart';

        cartModalBody.classList.add('col-md-12');
        cartModalBody.classList.remove('col-md-6');

        const emptyCartIcon = document.createElement('i');
        emptyCartIcon.classList.add(
            'fas',
            'fa-shopping-cart',
            'text-primary',
            'mr-2'
        ); // Add the desired Font Awesome class names to the icon

        const emptyCartMessage = document.createElement('p');
        emptyCartMessage.textContent = 'Your cart is empty.';

        cartModalBody.innerHTML = ''; // Clear the content of cartModalBody
        cartModalBody.appendChild(emptyCartIcon); // Append the emptyCartIcon to cartModalBody
        cartModalBody.appendChild(emptyCartMessage); // Append the emptyCartMessage to cartModalBody

        const cartSummary = document.querySelector('.Summary');
        cartSummary.innerHTML = '';
    }

};

// Call showSelectedContents() whenever necessary, such as when an item is selected or when the modal is opened.
showSelectedContents();
const removeCardFromCart = (index) => {
    const selectedCardIndex = selectedCardsData[index];
    const card = selectableCards[selectedCardIndex];
    const button = card.querySelector('.btn');
    card.classList.remove('selected');
    button.textContent = 'Select';
    button.classList.remove('btn-outline-danger');
    button.classList.add('btn-outline-success');
    counter--;
    selectedCardsData.splice(index, 1);

    // Update the floating button badge
    floatingButtonBadge.textContent = counter;
    if (counter > 0) {
        floatingButtonBadge.style.display = 'block';
    } else {
        floatingButtonBadge.style.display = 'none';
    }

    // Update cart contents
    showSelectedContents();
};

// Update cart contents when the modal is shown
cartModal.addEventListener('show.bs.modal', showSelectedContents);