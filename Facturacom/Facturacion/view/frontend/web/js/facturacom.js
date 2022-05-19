
require ([
  'jquery',
  'Magento_Ui/js/modal/alert'
], function($, alert){
  
  //Front client area functions
  //vars
  var order_data, customer_data, invoice_data;

  String.prototype.capitalize = function() {
    return this.replace(/(?:^|\s)\S/g, function(a) { return a.toUpperCase(); });
  };

  Number.prototype.formatMoney = function(c, d, t){
    var n = this,
    c = isNaN(c = Math.abs(c)) ? 2 : c,
    d = d == undefined ? "." : d,
    t = t == undefined ? "," : t,
    s = n < 0 ? "-" : "",
    i = parseInt(n = Math.abs(+n || 0).toFixed(c)) + "",
    j = (j = i.length) > 3 ? j % 3 : 0;
    return s + (j ? i.substr(0, j) + t : "") + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + t) + (c ? d + Math.abs(n - i).toFixed(c).slice(2) : "");
  };

  $(".input-cap").on('input', function(evt) {
    var input = $(this);
    var start = input[0].selectionStart;

    $(this).val(function (_, val) {
      return val.capitalize();
    });
    input[0].selectionStart = input[0].selectionEnd = start;
  });

  $(".input-upper").on('input', function(evt) {
    var input = $(this);
    var start = input[0].selectionStart;
    $(this).val(function(_, val){
      return val.toUpperCase();
    });
    input[0].selectionStart = input[0].selectionEnd = start;
  });

  $('#f-rfc').bind('keypress', function (event) {
    var keyCode = event.keyCode || event.which
    if (keyCode == 8 || (keyCode >= 35 && keyCode <= 40)) {
      return;
    }

    var regex = new RegExp("^[a-zA-Z0-9]+$");
    var key = String.fromCharCode(!event.charCode ? event.which : event.charCode);

    if (!regex.test(key)) {
      event.preventDefault();
      return false;
    }
  });


  //form-two functions

  /* Validate forms */
  function fillInvoiceContainer(order_data, customer_data, products){
    $('#invoice-id').hide();
    $('#invoice-date').hide();
    //receptor
    $('#receptor-nombre').html(`<b>Razon social: </b>${customer_data.RazonSocial}`);
    $('#receptor-rfc').html(`<b>RFC: </b>${customer_data.RFC}`);
    $('#receptor-regimen').html(`<b>Régimen Fiscal: </b>${customer_data.Regimen}`);
    $('#receptor-direccion').html(`<b>Calle: </b>${customer_data.Calle + ' ' + customer_data.Numero + ' ' + customer_data.Interior}`);
    $('#receptor-direccion-zone').html(`<b>Colonia: </b>${customer_data.Colonia + '. CP: ' + customer_data.CodigoPostal}`);
    $('#receptor-direccion-zone-city').html(`<b>Ciudad: </b>${customer_data.Ciudad + ', ' + customer_data.Estado + ', México.'}`);
    
    let body = '';

    for (const item_id in products){ // Iteramos el objeto

      const product = products[item_id];

      body += `<tr>
                <td>${product['name']}</td>
                <td>$ ${(Number(product['price'])).formatMoney(6, '.', ',')}</td>
                <td>${product['qty']}</td>
                <td>$ ${(Number(product['subtotal'])).formatMoney(6, '.', ',')}</td>
                <td>$ ${(Number(product['discount'])).formatMoney(6, '.', ',')}</td>
                <td>`;

      for (const type in product['taxes']){
        body += `<p>${type} ${product['taxes'][type]['percent']}% :  $${(Number(product['taxes'][type]['amount'])).formatMoney(6, '.', ',')}</p>`;
      };

      body += `</td>
                <td>$ ${(Number(product['total'])).formatMoney(6, '.', ',')}</td>
               </tr>`;
      
    }

    $('#datails-body').html(body);

    var payment_method;

    if(order_data['descuento_calculado'] > 0){

      $('#td-discount #invoice-discount').text('$ ' + Number(order_data['descuento_calculado']).formatMoney(6, '.', ','));
      $('#td-discount').css({'display':'table-row'});
    }

    let taxes_html = '';

    order_data['impuestos_calculados'].forEach(impuesto => {

      taxes_html += `
        <div>
          <div class="title-totals">${impuesto.type} (${impuesto.percent}%): </div>
          <div class="amount-totals"><span>$ ${Number(impuesto.amount).formatMoney(6, '.', ',')}</span></div>
        </div>
        `;

    });

    if(taxes_html != ''){
      $('#td-taxes').html(taxes_html);
    }
  
    $('#invoice-pmethod').text(payment_method); //order_data.payment_details.paid (para saber si está pagado)
    $('#invoice-subtotal').text('$' + Number(order_data['subtotal_calculado']).formatMoney(6, '.', ','));
    $('#invoice-total').text('$'+ Number(order_data['total_calculado']).formatMoney(6, '.', ','));
  }

  function fillFormTwo(data){

    //contacto
    $('#uid').val(data.UID);
    $('#general-nombre').val(data.Contacto.Nombre);
    $('#general-apellidos').val(data.Contacto.Apellidos);
    $('#general-email').val(data.Contacto.Email);

    $('#fiscal-rfc').val(data.RFC);

    if(data.Regimen != null){
      $('#fiscal-regimen').val(data.RegimenClave);
    }

    $('#fiscal-nombre').val(data.RazonSocial);
    $('#fiscal-calle').val(data.Calle);
    $('#fiscal-exterior').val(data.Numero);
    $('#fiscal-interior').val(data.Interior);
    $('#fiscal-colonia').val(data.Colonia);
    $('#fiscal-delegacion').val(data.Delegacion);
    $('#fiscal-municipio').val(data.Ciudad);
    $('#fiscal-estado').val(data.Estado);

    let pais = data.Pais; // esto lo debe devolver el API.

    if (pais == undefined || pais == '') {
        $('#fiscal-pais').val('MEX');
    } else {
        $('#fiscal-pais').val(pais);
    }

    $('#fiscal-cp').val(data.CodigoPostal);
    $('#fiscal-telefono').val(data.Contacto.Telefono);

    if (pais != "MEX") {
        $('#fiscal-numregidtrib').val(data.NumRegIdTrib);
        $('#field-numregidtrib').css({'display':'table'});
    }

    $('#step-two [type=input]').removeAttr('readonly');
  }

  function enableFormTwo(b){
    if(b == true){
      $('#general-nombre').removeAttr('readonly');
      $('#general-apellidos').removeAttr('readonly');
      $('#general-email').removeAttr('readonly');

      $('#fiscal-rfc').removeAttr('readonly');
      $('#fiscal-regimen').removeAttr('disabled');
      $('#fiscal-nombre').removeAttr('readonly');
      $('#fiscal-calle').removeAttr('readonly');
      $('#fiscal-exterior').removeAttr('readonly');
      $('#fiscal-interior').removeAttr('readonly');
      $('#fiscal-colonia').removeAttr('readonly');
      $('#fiscal-ciudad').removeAttr('readonly');
      $('#fiscal-delegacion').removeAttr('readonly');
      $('#fiscal-municipio').removeAttr('readonly');
      $('#fiscal-estado').removeAttr('readonly');
      $('#fiscal-pais').removeAttr('readonly');
      $('#fiscal-cp').removeAttr('readonly');
      $('#fiscal-telefono').removeAttr('readonly');
      $('#step-two-button-edit').val('Cancelar');
      var $labels = $("#f-step-two-form label[for]");
      $labels.css({'border-color':'#67BA2F'});

    }else{
      $('#general-nombre').attr('readonly','readonly');
      $('#general-apellidos').attr('readonly','readonly');
      $('#general-email').attr('readonly','readonly');
      $('#fiscal-rfc').attr('readonly','readonly');
      $('#fiscal-regimen').attr('disabled', 'disabled');
      $('#fiscal-nombre').attr('readonly','readonly');
      $('#fiscal-calle').attr('readonly','readonly');
      $('#fiscal-exterior').attr('readonly','readonly');
      $('#fiscal-interior').attr('readonly','readonly');
      $('#fiscal-colonia').attr('readonly','readonly');
      $('#fiscal-ciudad').attr('readonly','readonly');
      $('#fiscal-delegacion').attr('readonly','readonly');
      $('#fiscal-municipio').attr('readonly','readonly');
      $('#fiscal-estado').attr('readonly','readonly');
      $('#fiscal-pais').attr('readonly','readonly');
      $('#fiscal-cp').attr('readonly','readonly');
      $('#fiscal-telefono').attr('readonly','readonly');
      $('#step-two-button-edit').val('Editar');
      var $labels = $("#f-step-two-form label[for]");
      $labels.css({'border-color':'#c2c2c2'});
    }
  }

  function validateForm(form, step){
    if(step == 1){

      var rfc_item   = $("#f-rfc");
      var order_item = $("#f-num-order");
      var email_item = $("#f-email");

      if(rfc_item.val().length == 0){
        $("label[for='"+rfc_item.attr('id')+"']").addClass("input_error");
        rfc_item.addClass("input_error");
      }else{
        $("label[for='"+rfc_item.attr('id')+"']").removeClass("input_error");
        rfc_item.removeClass("input_error");
      }

      if(order_item.val().length == 0){
        $("label[for='"+order_item.attr('id')+"']").addClass("input_error");
        order_item.addClass("input_error");
      }else{
        $("label[for='"+order_item.attr('id')+"']").removeClass("input_error");
        order_item.removeClass("input_error");
      }

      if(email_item.val().length == 0){
        $("label[for='"+email_item.attr('id')+"']").addClass("input_error");
        email_item.addClass("input_error");
      }else{
        $("label[for='"+email_item.attr('id')+"']").removeClass("input_error");
        email_item.removeClass("input_error");
      }

      if(rfc_item.val().length > 13 || rfc_item.val().length < 12){
        $("label[for='"+rfc_item.attr('id')+"']").addClass("input_error");
        rfc_item.addClass("input_error");
        return false;
      }

      if( rfc_item.val().length > 0 && order_item.val().length > 0 && email_item.val().length > 0 ){
        rfc_item.removeClass("input_error");
        order_item.removeClass("input_error");
        email_item.removeClass("input_error");
        $("label[for='"+rfc_item.attr('id')+"']").removeClass("input_error");
        $("label[for='"+order_item.attr('id')+"']").removeClass("input_error");
        $("label[for='"+email_item.attr('id')+"']").removeClass("input_error");

        return true;
      }else{
        alert({
          title: 'Datos necesarios',
          content: `<p>Por favor llena los campos del formulario</p>`,
          modalClass: 'alert',
        });
      }


    }else if(step == 2){

      var isValid = [];
      var chkForInvalidAmount = [];

      $('#f-step-two-form .f-input').each(function () {

        var item = $(this);

        if(item.attr('id') == "fiscal-delegacion" || item.attr('id') == "fiscal-interior" ){
          return;
        }

        if(item.val().length == 0){
          $("label[for='"+item.attr('id')+"']").addClass("input_error");
          item.addClass("input_error");
          if ($( "#fiscal-pais option:selected" ).val() != 'MEX') {
              isValid.push("false");
          }
        }else{
          $("label[for='"+item.attr('id')+"']").removeClass("input_error");
          item.removeClass("input_error");
          isValid.push("true");
        }
      });

      if($('#fiscal-regimen').val().length == 0){
        $("label[for='fiscal-regimen']").addClass("input_error");
        $('#fiscal-regimen').addClass("input_error");
        isValid.push("false");
      } else {
        $("label[for='fiscal-regimen']").removeClass("input_error");
        $('#fiscal-regimen').removeClass("input_error");
        isValid.push("true");
      }

      var valid = $.inArray( "false", isValid );

      if(valid == -1){
        return true;
      }else{

        alert({
          title: 'Datos necesarios',
          content: `<p>Por favor completa y/o corrige los datos.</p>`,
          modalClass: 'alert',
        });
      }

    }
    return false;
  }

  $('#step-two-button-edit').click(function(e){
    e.preventDefault();
    var b = $(this).attr('data-b');

    if(b == 1){
      enableFormTwo(true);
      $(this).attr('data-b', 0);
      $("#f-step-two-form #apimethod").val("update");
      $("#step-two-button-next").val("Actualizar");
    }else{
      fillFormTwo(customer_data.Data);
      enableFormTwo(false);
      $(this).attr('data-b', 1);
      $("#f-step-two-form #apimethod").val("create");
      $("#step-two-button-next").val("Siguiente");
    }

  });

  $('.f-back').click(function(e){
    e.preventDefault();
    var form = $(this).attr("data-f");
    clearData(form);
  });

  $("#select-payment-t").change(function(){
    var selected_method = $( "#select-payment-t option:selected" ).val();

    if(selected_method == 03 || selected_method == 04 || selected_method == 28){
      $("#num-cta-box").fadeIn('fast');
    }else{
      $("#num-cta-box").fadeOut('fast');
    }
  });

  $("#fiscal-pais").change(function(){
    var selected_country = $( "#fiscal-pais option:selected" ).val();

    if(selected_country != 'MEX'){
        // show numregidtrib
        $('#fiscal-numregidtrib').val(data.NumRegIdTrib);
        $('#field-numregidtrib').css({'display':'table'});
    }else{
        // hide numregidtrib
        $('#fiscal-numregidtrib').val('');
        $('#field-numregidtrib').css({'display':'none'});
    }
  });

  function clearData(step){
    if(step == 2){
      $("#f-step-two-form").trigger("reset");
      $("#step-two").stop().hide();
      $(".customer-data").css({"background-color":"#9B9B9B"});
      $(".search-order").css({"background-color":"#942318"});

      $("#step-one").stop().fadeIn('slow');
    }else if(step == 3){
      $("#step-three").stop().hide();
      $(".verify-order").css({"background-color":"#9B9B9B"});
      $(".customer-data").css({"background-color":"#9B9B9B"});
      $('.search-order').css({ "background-color" : '#942318' });
      $("#step-two").stop().fadeIn('slow');
    }
  }


  //STEP ONE
  $('#f-step-one-form').submit(function(e){
    e.preventDefault();

    if( !validateForm($(this), 1) ) {
      return false;
    }

    $('.f-welcome-container').fadeOut('fast');
    $("#step-one .loader_content").show();

    form_data = $(this).serializeArray();

    data = {
      rfc    : form_data[0].value,
      order  : form_data[1].value,
      email  : form_data[2].value,
    }
    var action_url = $('#siteurl').val() + 'facturacion/index/one';
    $.post(action_url, data, function(response) {
      $("#step-one .loader_content").hide();

      /*
      var customerData = getCookie('customer');
      var orderData    = getCookie('order');
      */
      if(response.error == 400){

        alert({
          title: 'No se puede facturar',
          content: `<p>${response.message}</p>`,
          modalClass: 'alert',
        });
        return false;
      }

      if(response.error == 300){
        $('#result-msg-title').text(response.message);

        $('#btn-success-pdf').show().attr('href',$('#siteurl').val() + 'facturacion/index/download?type=pdf&uid='+response.data.uid);
        $('#btn-success-xml').show().attr('href',$('#siteurl').val() + 'facturacion/index/download?type=xml&uid='+response.data.uid);

        $('#step-one').stop().hide();
        $('#step-four').stop().fadeIn('slow');
        return false;
      }

      customer_data = response.data.customer;
      order_data = response.data.order;

      if(customer_data.status == "success"){
        $('#f-step-two-form').children('#fiscal-rfc').val(customer_data.Data.RFC);
      }

      if(customer_data.status != "error"){
        fillFormTwo(customer_data.Data);
      }else{
        $('#step-two-button-edit').hide();
        enableFormTwo(true);
      }

      $('#step-one').stop().hide();
      $('#step-two').stop().fadeIn('slow');

    }, 'json');

    return false;
  });

  //STEP TWO
  $('#f-step-two-form').submit(function(e){
    e.preventDefault();

    $("#step-two .loader_content").show();

    if( !validateForm($(this), 2) ) {
      $("#step-two .loader_content").hide();
      return false;
    }

    form_data = $(this).serializeArray();

    let posicion = 0;

    data = {
      api_method    : form_data[posicion++].value,
      uid           : form_data[posicion++].value,
      g_nombre      : form_data[posicion++].value,
      g_apellidos   : form_data[posicion++].value,
      g_email       : form_data[posicion++].value,
      f_telefono    : form_data[posicion++].value,
      f_nombre      : form_data[posicion++].value,
      f_rfc         : form_data[posicion++].value
    };
    
    if (form_data[8].name == 'fiscal-regimen'){
      data.f_regimen = form_data[posicion++].value;
    } else {
      data.f_regimen = $('#fiscal-regimen').val();
    }

    data.f_calle = form_data[posicion++].value;
    data.f_exterior = form_data[posicion++].value;
    data.f_interior = form_data[posicion++].value;
    data.f_colonia = form_data[posicion++].value;
    data.f_municipio = form_data[posicion++].value;
    data.f_estado = form_data[posicion++].value;
    data.f_pais = form_data[posicion++].value;
    data.f_cp = form_data[posicion++].value;
    data.f_numregidtrib = form_data[posicion++].value;


    var action_url = $('#siteurl').val() + 'facturacion/index/two';
    $.post(action_url, data, function(response) {
      $("#step-two .loader_content").hide();

      if(response.error != 200){
        alert({
          title: 'Error',
          content: `<p>${response.message}</p>`,
          modalClass: 'alert',
        });

      }else{
        fillInvoiceContainer(response.data.order, response.data.customer.Data, response.data.line_items);
        $('#step-two').stop().hide();
        $('#step-three').stop().fadeIn('slow');
      }
    }, 'json');
  });

  //STEP THREE
  $('#step-three-button-next').click(function(e){
    e.preventDefault();
    $("#step-three .loader_content").show();

    let selected_method_t = $( "#select-payment-t option:selected" ).val();
    let selected_uso = $( "#select-uso option:selected" ).val();
    let num_account  = $( "#f-num-cta" ).val();

    if(selected_method_t == 0 || selected_uso == 0){
      $("#step-three .loader_content").hide();
      alert({
        title: 'Campos necesarios',
        content: `<p>Por favor selecciona una forma de pago y un uso de CFDI.</p>`,
        modalClass: 'alert',
      });
      return false;
    }

    if(selected_method_t == 3 || selected_method_t == 4 || selected_method_t == 28){
      if(num_account == ""){
        $("#step-three .loader_content").hide();
        alert({
          title: 'Campos necesarios',
          content: `<p>Por favor ingresa los últimos 4 dí­gitos de tu cuenta o tarjeta.</p>`,
          modalClass: 'alert',
        });
        return false;
      }
    }

    data = {
      payment_m     : selected_method_t,
      num_cta_m     : num_account,
      uso           : selected_uso
    }

    var action_url = $('#siteurl').val() + 'facturacion/index/three';
    $.post(action_url, data, function(response) {
      $("#step-three .loader_content").hide();

      if(response.error == 400){

          let msg = "";
          
          if (typeof response.data.message == 'object'){
            msg += response.data.message.message;
          } else {
            msg += response.data.message;
          }
          
          alert({
            title: 'No se pudo facturar el pedido',
            content: `<p>${msg}</p>`,
            modalClass: 'alert',
          });

      }else{
        $('#btn-success-pdf').show().attr('href',$('#siteurl').val() + 'facturacion/index/download?type=pdf&uid='+response.data.uid);
        $('#btn-success-xml').show().attr('href',$('#siteurl').val() + 'facturacion/index/download?type=xml&uid='+response.data.uid);

        $("#step-three").stop().hide();
        $("#step-four").stop().fadeIn("slow");
      }

    }, 'json');


  });

});
