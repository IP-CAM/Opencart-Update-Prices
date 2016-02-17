<?php echo $header; ?>
<div id="content">
  <div class="breadcrumb">
    <?php foreach ($breadcrumbs as $breadcrumb) { ?>
    <?php echo $breadcrumb['separator']; ?><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a>
    <?php } ?>
  </div>
  <?php if ($error_warning) { ?>
  <div class="warning"><?php echo $error_warning; ?></div>
  <?php } ?>
  <div class="box">
    <div class="heading">
      <h1><img src="view/image/module.png" alt="" /> <?php echo $heading_title; ?></h1>
      <div class="buttons"><a onclick="$('#form').submit();" class="button"><?php echo $button_save; ?></a><a href="<?php echo $cancel; ?>" class="button"><?php echo $button_cancel; ?></a></div>
    </div>
    
    <div class="content">
      <form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="form">
        <?php if($act == 'index'){?>
          <table class="form">
            <tr>
              <td><span class="required">*</span> Адрес xml файла: </td>
              <td><input name="source_url" value="<?php echo $source_url; ?>" /></td>     
            </tr>
            <tr>
              <td><span class="required">*</span> Курс перевода USD в RUR: </td>
              <td><input name="from_usd" value="<?php echo $from_usd; ?>" /></td>     
            </tr>

            <!--<tr>
              <td><span class="required">*</span> Наценки: </td>
              <td><?php if(count($price_up)){echo '<a>Изменить наценки</a>';}else{ echo 'Нет сохраненных наценок, запустите обновление цен.';}?></td>     
            </tr>-->
            <tr>
              <td></td>
              <td><a href="<?php echo $link_update; ?>" class="button">Обновить цены</a></td>
            </tr>
          </table>
        <?php }else if($act == 'update'){ ?>
        <input type="hidden" name="source_url" value="<?php echo $source_url; ?>" />
        <input type="hidden" name="from_usd" value="<?php echo $from_usd; ?>" />
          <table class="form">
            <thead>
              <tr>
                <td>Код товара(SKU)</td>
                <td>Наименование</td>
                <td>Старая цена</td>
                <td>Новая цена</td>
                <td>Наценка</td>
                <td>Цена с наценкой</td>
              </tr>
            </thead>

            <?php $row = 0; 
              foreach ($products as $product) {?>
            <tr id="item<?php echo $row; ?>">    
                <td><?php echo $product['sku']; ?></td>
                <td><?php echo $product['name']; ?></td>
                <td><?php echo $product['price']; ?> руб.</td>
                <td><?php echo $product['new_price']; ?> руб.</td>
                <td><input class="up_percent" onchange="update_price(<?php echo $row; ?>);" style="width:50px;" name="prices[<?php echo $row; ?>][up_percent]" type="number" value="0">% +<input class="up_rub" onchange="update_price(<?php echo $row; ?>);"  name="prices[<?php echo $row; ?>][up_rub]" style="width:50px;" type="number" value="0"> руб.</td>
                <td><span class="new_price_up"></span> руб.</td>
                <input type="hidden" name="prices[<?php echo $row; ?>][sku]" value="<?php echo $product['sku']; ?>"/>
                <input class="new_price" type="hidden" name="prices[<?php echo $row; ?>][price]" value="<?php echo $product['new_price']; ?>"/>
            </tr>
             <?php $row++; }?>
          </table>
        <?php }?>
      </form>
     
      <?php if ($error_code) { ?>
        <span class="error"><?php echo $error_code; ?></span>
      <?php } ?></td> 
    </div>
  </div>
</div>
<script type="text/javascript"><!--

function update_price(i){
  var percent = parseInt($('#item' + i + ' td .up_percent').val());
  var rub = parseInt($('#item' + i + ' td .up_rub').val());
  var price = parseInt($('#item' + i + ' .new_price').val());
  console.log(price + ": " + rub + ": " + percent);
  $('#item' + i + ' .new_price_up').html(price + (percent * price / 100) + rub);
}
//--></script>
<?php echo $footer; ?>
