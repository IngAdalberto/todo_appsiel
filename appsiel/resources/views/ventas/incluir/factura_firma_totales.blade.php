<div class="table-responsive">
    <table class="table table-bordered">
        <tr>
            <td rowspan="4" width="70%"> <b> Firma del aceptante: </b> <br> </td>
            <td style="text-align: right; font-weight: bold;" class="totl-top"> Subtotal: &nbsp; </td>
            <td style="text-align: right; font-weight: bold;" class="totl-top"> $ &nbsp;{{ number_format($subtotal, 2, ',', '.') }} </td>
        </tr>
        <tr>
            <td style="text-align: right; font-weight: bold;" class="totl-mid"> Descuentos: &nbsp; </td>
            <td style="text-align: right; font-weight: bold;" class="totl-mid"> - $ &nbsp;{{ number_format($total_descuentos, 2, ',', '.') }} </td>
        </tr>
        <tr>
            <td style="text-align: right; font-weight: bold;" class="totl-mid"> Impuestos: &nbsp; </td>
            <td style="text-align: right; font-weight: bold;" class="totl-mid"> + $ &nbsp;{{ number_format($total_impuestos, 2, ',', '.') }} </td>
        </tr>
        <tr>
            <td style="text-align: right; font-weight: bold;" class="totl-bottom"> Total: &nbsp; </td>
            <td style="text-align: right; font-weight: bold;" class="totl-bottom"> $ &nbsp;{{ number_format($total_factura, 2, ',', '.') }} </td>
        </tr>
    </table>
</div>