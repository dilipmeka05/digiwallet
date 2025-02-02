@extends('layouts.app')

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">Wallet Dashboard</div>
                <div class="card-body">
                    <!-- Currency Selection -->
                    <div class="form-group">
                        <label for="currencySelect">Select Currency:</label>
                        <select id="currencySelect" class="form-control">
                            <option value="USD">USD</option>
                            <option value="EUR">EUR</option>
                            <option value="GBP">GBP</option>
                            <!-- more currencies as needed -->
                        </select>
                    </div>

                    <h5>Your Wallet Balance: <span id="balance">Loading...</span> <span id="currencySymbol">USD</span></h5>

                    <button class="btn btn-success" id="addFundsBtn">Add Funds</button>
                    <button class="btn btn-danger" id="withdrawFundsBtn">Withdraw Funds</button>
                    <button class="btn btn-info" id="transferFundsBtn">Transfer Funds</button>

                    <hr>
                    <h5>Transaction History</h5>
                    <ul id="transactionHistory">
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endsection
