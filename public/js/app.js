$(document).ready(function () {
    const apiUrl = '/api/';
    let token = localStorage.getItem('token');

    if (token) {
        fetchBalance();
        fetchTransactions();
        fetchCurrency(); 
    }

    // Register form submission
    $('#registerForm').on('submit', function (e) {
        e.preventDefault();

        const data = {
            name: $('#name').val(),
            email: $('#email').val(),
            password: $('#password').val(),
        };

        $.ajax({
            url: apiUrl + 'register',
            type: 'POST',
            data: data,
            success: function () {
                window.location.href = '/login';
            },
            error: function () {
                alert('Registration failed.');
            }
        });
    });

    // Login form submission
    $('#loginForm').on('submit', function (e) {
        e.preventDefault();

        const data = {
            email: $('#loginEmail').val(),
            password: $('#loginPassword').val(),
        };

        $.ajax({
            url: apiUrl + 'login',
            type: 'POST',
            data: data,
            success: function (response) {
                localStorage.setItem('token', response.token);
                window.location.href = '/wallet/dashboard';
            },
            error: function () {
                alert('Login failed.');
            }
        });
    });

    // Fetch the balance of the user's wallet
    function fetchBalance() {
        $.ajax({
            url: apiUrl + 'balance',
            type: 'GET',
            headers: { 'Authorization': 'Bearer ' + token },
            success: function (response) {
                $('#balance').text(response.balance);
            }
        });
    }

    // Fetch transaction history
    function fetchTransactions() {
        $.ajax({
            url: apiUrl + 'transaction-history',
            type: 'GET',
            headers: { 'Authorization': 'Bearer ' + token },
            success: function (response) {
                let transactions = response.transactions;
                $('#transactionHistory').empty();
                transactions.forEach(transaction => {
                    $('#transactionHistory').append(`
                        <li>${transaction.type}: $${transaction.amount} on ${transaction.created_at}</li>
                    `);
                });
            }
        });
    }

    // Fetch current currency
    function fetchCurrency() {
        $.ajax({
            url: apiUrl + 'get-currency',
            type: 'GET',
            headers: { 'Authorization': 'Bearer ' + token },
            success: function (response) {
                $('#currencySymbol').text(response.currency);
                $('#currencySelect').val(response.currency); 
            },
            error: function () {
                alert('Failed to fetch currency.');
            }
        });
    }

    // Add funds
    $('#addFundsBtn').on('click', function () {
        let amount = prompt("Enter the amount to add:");
        if (amount) {
            $.ajax({
                url: apiUrl + 'add-funds',
                type: 'POST',
                headers: { 'Authorization': 'Bearer ' + token },
                data: { amount: amount },
                success: function () {
                    fetchBalance();
                }
            });
        }
    });

    // Withdraw funds
    $('#withdrawFundsBtn').on('click', function () {
        let amount = prompt("Enter the amount to withdraw:");
        if (amount) {
            $.ajax({
                url: apiUrl + 'withdraw-funds',
                type: 'POST',
                headers: { 'Authorization': 'Bearer ' + token },
                data: { amount: amount },
                success: function () {
                    fetchBalance();
                }
            });
        }
    });

    // Transfer funds
    $('#transferFundsBtn').on('click', function () {
        let recipientEmail = prompt("Enter recipient's email:");
        let amount = prompt("Enter the amount to transfer:");

        if (recipientEmail && amount) {
            $.ajax({
                url: apiUrl + 'transfer-funds',
                type: 'POST',
                headers: { 'Authorization': 'Bearer ' + token },
                data: { recipient_email: recipientEmail, amount: amount },
                success: function () {
                    fetchBalance();
                    fetchTransactions();
                }
            });
        }
    });

    // Fetch the exchange rates and update balance based on selected currency
    function updateCurrencyConversion(fromCurrency, toCurrency) {
        $.ajax({
            url: apiUrl + 'convert-currency',
            type: 'POST',
            headers: { 'Authorization': 'Bearer ' + token },
            data: {
                from_currency: fromCurrency,
                to_currency: toCurrency,
                amount: $('#balance').text()
            },
            success: function (response) {
                if (response.convertedAmount !== undefined) {
                    $('#balance').text(response.convertedAmount);
                    $('#currencySymbol').text(toCurrency); 
                }
            },
            error: function () {
                alert('Currency conversion failed.');
            }
        });
    }

// Update currency when user selects from dropdown
$('#currencySelect').on('change', function () {
    let selectedCurrency = $(this).val();
    let currentCurrency = $('#currencySymbol').text(); 

    if (selectedCurrency !== currentCurrency) {
        $('#currencySymbol').text(selectedCurrency);
        updateCurrencyConversion(currentCurrency, selectedCurrency);
        $.ajax({
            url: apiUrl + 'update-currency',
            type: 'POST',
            headers: { 'Authorization': 'Bearer ' + token },
            data: { currency: selectedCurrency },
            success: function (response) {
                if (response.success) {
                    fetchBalance();
                } else {
                    alert('Failed to update currency.');
                }
            },
            error: function () {
                alert('Failed to update currency.');
            }
        });
    }
});

});
