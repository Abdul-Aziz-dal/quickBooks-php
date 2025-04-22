<?php
  require_once(__DIR__ . '/data-service.php');

$OAuth2LoginHelper = $dataService->getOAuth2LoginHelper();

$accessTokenJson = "No Access Token Generated Yet";

if (isset($_SESSION['sessionAccessToken'])) {
    $accessToken = $_SESSION['sessionAccessToken'];
        $accessTokenJson = array(
            'token_type' => 'bearer',
            'access_token' => $accessToken->getAccessToken(),
            'refresh_token' => $accessToken->getRefreshToken(),
            'x_refresh_token_expires_in' => $accessToken->getRefreshTokenExpiresAt(),
            'expires_in' => $accessToken->getAccessTokenExpiresAt()
        );
    
}

// Always update auth URL
$authUrl = $OAuth2LoginHelper->getAuthorizationCodeURL();
$_SESSION['authUrl'] = $authUrl;
?>

<!DOCTYPE html>
<html>
<head>
    <link rel="apple-touch-icon icon shortcut" type="image/png" href="https://plugin.intuitcdn.net/sbg-web-shell-ui/6.3.0/shell/harmony/images/QBOlogo.png">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
    <link rel="stylesheet" href="https://netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="views/common.css">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <script>
        var url = '<?php echo $authUrl; ?>';

        var OAuthCode = function(url) {
            this.loginPopup = function () {
                var parameters = "location=1,width=800,height=650";
                parameters += ",left=" + (screen.width - 800) / 2 + ",top=" + (screen.height - 650) / 2;

                var win = window.open(url, 'connectPopup', parameters);
                var pollOAuth = window.setInterval(function () {
                    try {
                        if (win.document.URL.indexOf("code") != -1) {
                            window.clearInterval(pollOAuth);
                            win.close();
                            location.reload();
                        }
                    } catch (e) {}
                }, 100);
            }

            this.logout = function(){
                $.ajax({
                    type: "GET",
                    url: "api/logout.php",
                }).done(function() {
                    location.reload();
                });
            }
        }

        var apiCall = function() {
            this.getCompanyInfo = function() {
                $.get("api/get-company-info.php", function(msg) {
                    $('#getCompanyInfo').html(msg);
                });
            }


            this.getCustomers = function() {
                $.get("api/get-customers.php", function(msg) {
                    $('#customersView').html(msg);
                });
            }

            this.getItems = function() {
                $.get("api/get-items.php", function(msg) {
                    $('#itemsView').html(msg);
                });
            }

            this.getInvoices = function() {
                $.get("api/get-invoices.php", function(resData) {
                if (resData.success && Array.isArray(resData.data)) {
                    let html = '<table class="table table-striped">';
                    html += '<thead><tr><th>ID</th><th>Doc Number</th><th>Customer Name</th><th>Total</th><th>Balance</th><th>Status</th><th>Date</th><th>Due</th><th>Email</th><th>Address</th><th>Download</th></tr></thead>';
                    html += '<tbody>';

                    resData.data.forEach(invoice => {
                        html += `<tr>
                            <td>${invoice.id}</td>
                            <td>${invoice.docNumber}</td>
                            <td>${invoice.customerRef}</td>
                            <td>${invoice.totalAmount}</td>
                            <td>${invoice.balance}</td>
                            <td>${invoice.status}</td>
                            <td>${invoice.txnDate}</td>
                            <td>${invoice.dueDate}</td>
                            <td>${invoice.billingEmail}</td>
                            <td>${invoice.billingAddress}</td>
                            <td><a href="${invoice.downloadLink}" download>Download</a></td>
                        </tr>`;
                    });

                    html += '</tbody></table>';
                  $('#invoicesView').html(html);
            } else {
                $('#invoicesView').html('<p>No invoices found.</p>');
            }
                 });

            }

            this.addCustomer = function () {
                let name = $('#customerName').val();
                let email = $('#customerEmailAddress').val();

                if (!name || !email) return alert("Name & email required");

                $.post("api/add-customer.php", { name: name, email: email }, function(response) {
                    $('#customerName').val("");
                    $('#customerEmailAddress').val("");
                    apiCall.getCustomers();
                });
            }

            this.createInvoice = function () {
                $.post("api/create-invoice.php", {}, function(response) {
                    alert("Invoice created successfully");
                }).fail(function () {
                    alert("Invoice creation failed");
                });
            }
        }

        var oauth = new OAuthCode(url);
        var apiCall = new apiCall();
    </script>
</head>
<body>

<div class="container">
    <h1>
        <a href="http://developer.intuit.com">
            <img src="views/quickbooks_logo_horz.png" id="headerLogo">
        </a>
    </h1>

    <hr>

    <p>If there is no access token or the access token is invalid, click the <b>Connect to QuickBooks</b> button below.</p>
    <pre id="accessToken" style="background-color:#efefef;overflow-x:scroll"><?php
        echo json_encode($accessTokenJson, JSON_PRETTY_PRINT); ?>
    </pre>

    <button type="button" class="btn btn-success" onclick="oauth.loginPopup()">
        <?php echo isset($_SESSION['sessionAccessToken']) ? "Connected with QuickBooks" : "Connect with QuickBooks"; ?>
    </button>

    <!-- <button type="button" class="btn btn-warning" onclick="oauth.logout()">Logout</button> -->

    <hr />

    <h5>Company info</h5>
    <pre id="getCompanyInfo"></pre>
    <button class="btn btn-primary" onclick="apiCall.getCompanyInfo()">Get Company Info</button>

    <br/><br/>
    <h5>Customers</h5>
    <pre id="customersView"></pre>
    <button class="btn btn-primary" onclick="apiCall.getCustomers()">Get Customers</button>

    <br/><br/>
    <h5>Items</h5>
    <pre id="itemsView"></pre>
    <button class="btn btn-primary" onclick="apiCall.getItems()">Get Items</button>

    <br/><br/>
    <h5>Invoices</h5>
    <pre id="invoicesView"></pre>
    <button class="btn btn-primary" onclick="apiCall.getInvoices()">Get Invoices</button>

    <br/><br/>
    <h5>Create Invoice</h5>
    <button class="btn btn-success" onclick="apiCall.createInvoice()">Create Invoice</button>

    <br/><br/>
    <h5>Add New Customer</h5>
    <form>
        <input type="text" id="customerName" placeholder="Customer name" class="form-control" />
        <br/>
        <input type="text" id="customerEmailAddress" placeholder="Customer email" class="form-control" />
        <br/>
        <button type="button" class="btn btn-success" onclick="apiCall.addCustomer()">Add Customer</button>
    </form>

    <hr/>
</div>

</body>
</html>
