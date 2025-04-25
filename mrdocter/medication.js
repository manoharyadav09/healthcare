document.addEventListener('DOMContentLoaded', function() {
    // DOM Elements
    const medicationGrid = document.getElementById('medicationGrid');
    const currentMeds = document.getElementById('currentMeds');
    const cartItems = document.getElementById('cartItems');
    const cartTotal = document.getElementById('cartTotal');
    const cartBadge = document.getElementById('cartBadge');
    const cartIcon = document.getElementById('cartIcon');
    const checkoutBtn = document.getElementById('checkoutBtn');
    const addMedBtn = document.getElementById('addMedBtn');
    const saveMedBtn = document.getElementById('saveMedBtn');
    const medSearch = document.getElementById('medSearch');
    const addMedModal = new bootstrap.Modal(document.getElementById('addMedModal'));
    const checkoutModal = new bootstrap.Modal(document.getElementById('checkoutModal'));
    
    // Data
    let medications = [];
    let cart = JSON.parse(localStorage.getItem('cart')) || [];
    let userMeds = JSON.parse(localStorage.getItem('userMeds')) || [];
    
    // Initialize
    loadMedications();
    renderCurrentMeds();
    renderCart();
    setupInjuryAccordion();
    
    // Event Listeners
    checkoutBtn.addEventListener('click', handleCheckout);
    addMedBtn.addEventListener('click', () => addMedModal.show());
    saveMedBtn.addEventListener('click', saveMedication);
    medSearch.addEventListener('input', filterMedications);
    document.querySelectorAll('.btn-recommendation').forEach(btn => {
        btn.addEventListener('click', showRecommendedMeds);
    });
    
    // Load sample medications
    function loadMedications() {
        medications = [
            {
                id: 1,
                name: 'Ibuprofen',
                description: 'Pain reliever and anti-inflammatory for headaches, muscle pain, and fever',
                price: 990,
                category: 'pain'
            },
            {
                id: 2,
                name: 'Acetaminophen',
                description: 'Pain reliever and fever reducer, gentler on stomach than ibuprofen',
                price: 599,
                category: 'pain'
            },
            {
                id: 3,
                name: 'Diphenhydramine',
                description: 'Antihistamine for allergy relief and as a sleep aid',
                price: 150,  // Updated from 599 (₹5.99)
                category: 'allergy'
            },
            {
                id: 4,
                name: 'Loratadine',
                description: 'Non-drowsy allergy relief for sneezing, runny nose, and itchy eyes',
                price: 120,  // Updated from 1299 (₹12.99)
                category: 'allergy'
            },
            {
                id: 5,
                name: 'Omeprazole',
                description: 'Acid reducer for heartburn and acid indigestion',
                price: 180,  // Updated from 1499 (₹14.99)
                category: 'digestive'
            },
            {
                id: 6,
                name: 'Pepto-Bismol',
                description: 'Relieves upset stomach, heartburn, nausea, and diarrhea',
                price: 110,  // Updated from 699 (₹6.99)
                category: 'digestive'
            },
            {
                id: 7,
                name: 'Hydrocortisone Cream',
                description: 'Topical anti-itch cream for insect bites and skin irritations',
                price: 130,  // Updated from 499 (₹4.99)
                category: 'topical'
            },
            {
                id: 8,
                name: 'Band-Aids',
                description: 'Adhesive bandages for minor cuts and scrapes',
                price: 100,  // Updated from 399 (₹3.99)
                category: 'first-aid'
            }
        ];
        
        renderMedications(medications);
    }
    
    // Render medications to the grid
    function renderMedications(meds) {
        medicationGrid.innerHTML = '';
        
        if (meds.length === 0) {
            medicationGrid.innerHTML = '<p class="text-muted">No medications found</p>';
            return;
        }
        
        meds.forEach(med => {
            const medItem = document.createElement('div');
            medItem.className = 'med-item';
            medItem.innerHTML = `
                <div class="ibuprofen.jpg">
                    <i class="fas fa-pills"></i>
                </div>
                <div class="med-info">
                    <h5 class="med-name">${med.name}</h5>
                    <p class="med-desc">${med.description}</p>
                </div>
                <div class="med-footer">
                    <span class="med-price">₹${med.price.toFixed(2)}</span>
                    <button class="add-to-cart" data-id="${med.id}">Add to Cart</button>
                </div>
            `;
            medicationGrid.appendChild(medItem);
        });
        
        // Add event listeners to all add-to-cart buttons
        document.querySelectorAll('.add-to-cart').forEach(btn => {
            btn.addEventListener('click', addToCart);
        });
    }
    
    // Filter medications based on search input
    function filterMedications() {
        const searchTerm = medSearch.value.toLowerCase();
        const filtered = medications.filter(med => 
            med.name.toLowerCase().includes(searchTerm) || 
            med.description.toLowerCase().includes(searchTerm)
        );
        renderMedications(filtered);
    }
    
    // Add medication to cart
    function addToCart(e) {
        const medId = parseInt(e.target.dataset.id);
        const medication = medications.find(m => m.id === medId);
        
        // Check if already in cart
        const existingItem = cart.find(item => item.id === medId);
        
        if (existingItem) {
            existingItem.quantity += 1;
        } else {
            cart.push({
                ...medication,
                quantity: 1
            });
        }
        
        // Save to localStorage and update UI
        localStorage.setItem('cart', JSON.stringify(cart));
        renderCart();
        
        // Show success animation
        showCartAlert(medication.name);
    }
    
    // Show cart notification
    function showCartAlert(medName) {
        const alert = document.createElement('div');
        alert.className = 'appointment-alert show';
        alert.innerHTML = `
            <div class="alert-icon">
                <i class="fas fa-cart-plus"></i>
            </div>
            <div class="alert-content">
                <h4>Added to Cart</h4>
                <p>${medName} has been added to your cart</p>
            </div>
            <button class="alert-close">&times;</button>
        `;
        
        document.body.appendChild(alert);
        
        // Close button functionality
        alert.querySelector('.alert-close').addEventListener('click', () => {
            alert.classList.remove('show');
            setTimeout(() => {
                alert.remove();
            }, 300);
        });
        
        // Auto-remove after 3 seconds
        setTimeout(() => {
            alert.classList.remove('show');
            setTimeout(() => {
                alert.remove();
            }, 300);
        }, 3000);
    }
    
    // Render cart items
    function renderCart() {
        if (cart.length === 0) {
            cartItems.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-shopping-cart"></i>
                    <p>Your cart is empty</p>
                </div>
            `;
            cartTotal.textContent = '₹0.00';
            cartBadge.textContent = '0 items';
            cartIcon.querySelector('.cart-count').textContent = '0';
            checkoutBtn.disabled = true;
            return;
        }
        
        checkoutBtn.disabled = false;

        let total = 0;
        
        cartItems.innerHTML = '';
        cart.forEach(item => {
            total += item.price * item.quantity;
            
            const cartItem = document.createElement('div');
            cartItem.className = 'cart-item';
            cartItem.innerHTML = `
                <div class="cart-item-info">
                    <div class="cart-item-name">${item.name}</div>
                    <div class="cart-item-price">₹${item.price.toFixed(2)} each</div>
                </div>
                <div class="cart-item-quantity">
                    <button class="quantity-btn decrease" data-id="${item.id}">-</button>
                    <span>${item.quantity}</span>
                    <button class="quantity-btn increase" data-id="${item.id}">+</button>
                    <i class="fas fa-times remove-item" data-id="${item.id}"></i>
                </div>
            `;
            cartItems.appendChild(cartItem);
        });
        
        // Update totals and badges
        cartTotal.textContent = `₹${total.toFixed(2)}`;
        cartBadge.textContent = `${cart.reduce((sum, item) => sum + item.quantity, 0)} items`;
        cartIcon.querySelector('.cart-count').textContent = cart.reduce((sum, item) => sum + item.quantity, 0);
        
        // Add event listeners to quantity buttons
        document.querySelectorAll('.quantity-btn').forEach(btn => {
            btn.addEventListener('click', updateCartQuantity);
        });
        
        document.querySelectorAll('.remove-item').forEach(btn => {
            btn.addEventListener('click', removeFromCart);
        });
    }
    
    // Update item quantity in cart
    function updateCartQuantity(e) {
        const medId = parseInt(e.target.dataset.id);
        const item = cart.find(item => item.id === medId);
        
        if (e.target.classList.contains('increase')) {
            item.quantity += 1;
        } else if (e.target.classList.contains('decrease')) {
            item.quantity -= 1;
            
            if (item.quantity < 1) {
                cart = cart.filter(item => item.id !== medId);
            }
        }
        
        localStorage.setItem('cart', JSON.stringify(cart));
        renderCart();
    }
    
    // Remove item from cart
    function removeFromCart(e) {
        const medId = parseInt(e.target.dataset.id);
        cart = cart.filter(item => item.id !== medId);
        localStorage.setItem('cart', JSON.stringify(cart));
        renderCart();
    }
    
    // Handle checkout
    function handleCheckout() {
        const cartItems = JSON.parse(localStorage.getItem('cart')) || [];
        if (cartItems.length === 0) {
            alert('Your cart is empty');
            return;
        }
    
        const total = cartItems.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    
        // Show success message immediately
        const orderSummary = document.getElementById('orderSummary');
        orderSummary.innerHTML = `
            <div class="order-details">
                <h5>Order Confirmation</h5>
                <p><strong>Order Total:</strong> ₹${total.toFixed(2)}</p>
                <p><strong>Items:</strong></p>
                <ul>
                    ${cartItems.map(item => `
                        <li>${item.name} (Qty: ${item.quantity}) - ₹${(item.price * item.quantity).toFixed(2)}</li>
                    `).join('')}
                </ul>
                <p class="text-success"><i class="fas fa-check-circle"></i> Your order has been placed successfully!</p>
            </div>
        `;
        
        const checkoutModal = new bootstrap.Modal(document.getElementById('checkoutModal'));
        checkoutModal.show();
    
        // Clear cart immediately
        localStorage.removeItem('cart');
        updateCartDisplay();
    
        // Process order in background
        fetch('save_medication_order.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                items: cartItems,
                total_amount: total,
                status: 'Processing'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.order_id) {
                // Update order confirmation with actual order ID
                orderSummary.querySelector('h5').textContent = `Order Confirmation #${data.order_id}`;
                
                // Refresh medication orders display
                if (typeof refreshMedicationOrders === 'function') {
                    refreshMedicationOrders();
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
    
        // Add event listener to modal's close button
        document.querySelector('#checkoutModal .btn-primary').addEventListener('click', function() {
            // Clear cart again to be safe
            localStorage.removeItem('cart');
            updateCartDisplay();
            window.location.href = 'medication.php';
        });
    }
    
    // Save new medication to user's list
    function saveMedication() {
        const name = document.getElementById('medName').value;
        const dosage = document.getElementById('medDosage').value;
        const frequency = document.getElementById('medFrequency').value;
        const instructions = document.getElementById('medInstructions').value;
        
        userMeds.push({
            id: Date.now(),
            name,
            dosage,
            frequency,
            instructions,
            dateAdded: new Date().toISOString()
        });
        
        localStorage.setItem('userMeds', JSON.stringify(userMeds));
        renderCurrentMeds();
        addMedModal.hide();
        document.getElementById('addMedForm').reset();
    }
    
    // Render user's current medications
    function renderCurrentMeds() {
        if (userMeds.length === 0) {
            currentMeds.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-prescription-bottle-alt"></i>
                    <p>No medications added yet</p>
                </div>
            `;
            return;
        }
        
        const medList = document.createElement('ul');
        medList.className = 'medication-list';
        
        userMeds.forEach(med => {
            const medItem = document.createElement('li');
            medItem.innerHTML = `
                <div class="med-info-container">
                    <div class="med-name">${med.name}</div>
                    <div class="med-dosage">${med.dosage} • ${med.frequency}</div>
                    ${med.instructions ? `<div class="med-instructions">${med.instructions}</div>` : ''}
                </div>
                <div class="med-actions">
                    <button class="med-action-btn edit" data-id="${med.id}">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="med-action-btn delete" data-id="${med.id}">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </div>
            `;
            medList.appendChild(medItem);
        });
        
        currentMeds.innerHTML = '';
        currentMeds.appendChild(medList);
        
        // Add event listeners to action buttons
        document.querySelectorAll('.edit').forEach(btn => {
            btn.addEventListener('click', editMedication);
        });
        
        document.querySelectorAll('.delete').forEach(btn => {
            btn.addEventListener('click', deleteMedication);
        });
    }
    
    // Edit medication
    function editMedication(e) {
        const medId = parseInt(e.target.closest('button').dataset.id);
        const med = userMeds.find(m => m.id === medId);
        
        // Fill form with medication data
        document.getElementById('medName').value = med.name;
        document.getElementById('medDosage').value = med.dosage;
        document.getElementById('medFrequency').value = med.frequency;
        document.getElementById('medInstructions').value = med.instructions || '';
        
        // Change save button to update
        saveMedBtn.textContent = 'Update Medication';
        saveMedBtn.onclick = function() {
            // Update medication
            med.name = document.getElementById('medName').value;
            med.dosage = document.getElementById('medDosage').value;
            med.frequency = document.getElementById('medFrequency').value;
            med.instructions = document.getElementById('medInstructions').value;
            
            localStorage.setItem('userMeds', JSON.stringify(userMeds));
            renderCurrentMeds();
            addMedModal.hide();
            document.getElementById('addMedForm').reset();
            
            // Reset save button
            saveMedBtn.textContent = 'Save Medication';
            saveMedBtn.onclick = saveMedication;
        };
        
        addMedModal.show();
    }
    
    // Delete medication
    function deleteMedication(e) {
        const medId = parseInt(e.target.closest('button').dataset.id);
        userMeds = userMeds.filter(m => m.id !== medId);
        localStorage.setItem('userMeds', JSON.stringify(userMeds));
        renderCurrentMeds();
    }
    
    // Show recommended medications for a condition
    function showRecommendedMeds(e) {
        const condition = e.target.dataset.condition;
        let recommended = [];
        
        switch(condition) {
            case 'cold':
                recommended = medications.filter(m => 
                    m.name.includes('Diphenhydramine') || 
                    m.name.includes('Acetaminophen') ||
                    m.category === 'pain'
                );
                break;
            case 'sprain':
                recommended = medications.filter(m => 
                    m.name.includes('Ibuprofen') || 
                    m.category === 'pain' ||
                    m.category === 'topical'
                );
                break;
            case 'fever':
                recommended = medications.filter(m => 
                    m.name.includes('Acetaminophen') || 
                    m.name.includes('Ibuprofen') ||
                    m.category === 'pain'
                );
                break;
        }
        
        renderMedications(recommended);
        
        // Scroll to medication grid
        medicationGrid.scrollIntoView({ behavior: 'smooth' });
    }
    
    // Setup injury information accordion
    function setupInjuryAccordion() {
        const injuries = [
            {
                title: 'Sprains',
                icon: 'fas fa-bone',
                description: 'A sprain is a stretching or tearing of ligaments — the tough bands of fibrous tissue that connect two bones together in your joints.',
                symptoms: [
                    'Pain',
                    'Swelling',
                    'Bruising',
                    'Limited ability to move the affected joint'
                ],
                treatments: [
                    'Rest',
                    'Ice',
                    'Compression',
                    'Elevation',
                    'Pain relievers'
                ]
            },
            {
                title: 'Strains',
                icon: 'fas fa-running',
                description: 'A strain is a stretching or tearing of muscle or tendon. A tendon is a fibrous cord of tissue that connects muscles to bones.',
                symptoms: [
                    'Pain',
                    'Muscle spasms',
                    'Swelling',
                    'Limited ability to move the affected muscle'
                ],
                treatments: [
                    'Rest',
                    'Ice',
                    'Compression',
                    'Elevation',
                    'Gentle stretching'
                ]
            },
            {
                title: 'Bruises',
                icon: 'fas fa-hand-holding-medical',
                description: 'A bruise forms when a blow breaks blood vessels near your skin\'s surface, allowing a small amount of blood to leak into the tissues under your skin.',
                symptoms: [
                    'Skin discoloration',
                    'Tenderness',
                    'Swelling'
                ],
                treatments: [
                    'Cold compress',
                    'Elevation',
                    'Pain relievers',
                    'Protection from further injury'
                ]
            },
            {
                title: 'Minor Burns',
                icon: 'fas fa-fire',
                description: 'First-degree burns affect only the outer layer of skin. The burn site is red, painful, dry, and with no blisters.',
                symptoms: [
                    'Redness',
                    'Pain',
                    'Minor swelling'
                ],
                treatments: [
                    'Cool water',
                    'Moisturizer',
                    'Pain relievers',
                    'Protection from sun'
                ]
            }
        ];
        
        const accordion = document.getElementById('injuryAccordion');
        
        injuries.forEach(injury => {
            const item = document.createElement('div');
            item.className = 'injury-item';
            item.innerHTML = `
                <div class="injury-header">
                    <div class="injury-title">
                        <div class="injury-icon">
                            <i class="${injury.icon}"></i>
                        </div>
                        ${injury.title}
                    </div>
                    <i class="fas fa-chevron-down injury-chevron"></i>
                </div>
                <div class="injury-content">
                    <p>${injury.description}</p>
                    <p><strong>Symptoms:</strong></p>
                    <ul>
                        ${injury.symptoms.map(s => `<li>${s}</li>`).join('')}
                    </ul>
                    <p><strong>Recommended Treatment:</strong></p>
                    <div class="treatment-tags">
                        ${injury.treatments.map(t => `<span class="treatment-tag">${t}</span>`).join('')}
                    </div>
                </div>
            `;
            accordion.appendChild(item);
        });
        
        // Add click event to headers
        document.querySelectorAll('.injury-header').forEach(header => {
            header.addEventListener('click', function() {
                const content = this.nextElementSibling;
                const chevron = this.querySelector('.injury-chevron');
                
                // Toggle content
                content.classList.toggle('show');
                chevron.classList.toggle('rotated');
            });
        });
    }
});