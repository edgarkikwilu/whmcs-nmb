<div class="container">
    <div class="row my-5">
        <div class="col-md-2"></div>
        <div class="col-md-8">
            <div class="card border-0 shadow-lg">
                <div class="card-body">
                    <h2 class="mb-3">Payment Details</h2>
                    <table class="table table-striped table-bordered">
                        <tr>
                            <td><strong>Invoice No: </strong></td> <td>{$invoiceId}</td>
                        </tr>
                      <tr><td><strong>Reference No: </strong></td> <td>{$transactionId}</td></tr>
                    </table>
                    
                           {if $status == "SUCCESS" }
                                  <div class="alert alert-success">
                                  Thank you for using our system. Your payment was processed successfully
                                  </div>
                            {else}
                              <div class="alert alert-danger">
                              Thank you for using our system. However, your payment failed and no deductions were made on your account. Please contact support for more information.
                              </div>
                            {/if}
                </div>
            </div>
        </div>
        <div class="col-md-2"></div>
    </div>
</div>  
