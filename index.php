<?php
/**
 * @param $taxableIncome
 * @return array
 */
echo "Tax Calculator !";
$config = json_decode(file_get_contents('config.json'), true);
$salary = json_decode(file_get_contents('salary.json'), true);

function tax_from_taxable_income($taxableIncome, $slab)
{
  $tax = [];
  $remaining_income = $taxableIncome;
  $current_per = 0;
  $last_slab = 0;

  foreach ($slab as $current_slab => $val) {
    if ($remaining_income > $val['inc']) {
      $tax[$current_slab]['inc'] = $val['inc'];
      $tax[$current_slab]['per'] = $val['per'];
      $tax[$current_slab]['tax'] = ($val['inc'] * $val['per']) / 100;
    } else {
      $tax[$current_slab]['inc'] = $remaining_income;
      $tax[$current_slab]['per'] = $val['per'];
      $tax[$current_slab]['tax'] = ($remaining_income * $val['per']) / 100;
      $remaining_income = 0;
      break;
    }
    $remaining_income = $remaining_income - $val['inc'];
    $current_per = $val['per'];
    $last_slab = $current_slab;
  }
  if ($remaining_income != 0) {
    $tax[$last_slab]['inc'] = $remaining_income;
    $tax[$last_slab]['per'] = $current_per;
    $tax[$last_slab]['tax'] = ($remaining_income * $current_per) / 100;
    $remaining_income = 0;
  }
  return $tax;
}

function taxable_income($total_salary, $config)
{
  // taxable income calculation
  $total_taxable['house_rent'] = $total_salary['house_rent'] - $config['house_rent_limit'];
  $total_taxable['conveyance'] = $total_salary['conveyance'] - $config['conveyance_limit'];

  $medical_basic_salary = ($total_salary['basic'] * $config['medical_basic_per']) / 100;
  if ($medical_basic_salary <= $config['medical_limit']) {
    $total_taxable['medical'] = $total_salary['medical'] - $medical_basic_salary;
    $medical_label = sprintf("%s %% of Basic (%s)", $config['medical_basic_per'], $medical_basic_salary);
  } else {
    $total_taxable['medical'] = $total_salary['medical'] - $config['medical_limit'];
    $medical_label = sprintf("(%s)", $config['medical_limit']);
  }
// taxable if more then limit
  if ($total_taxable['house_rent'] < 0) {
    $total_taxable['house_rent'] = 0;
  }
  if ($total_taxable['conveyance'] < 0) {
    $total_taxable['conveyance'] = 0;
  }
  if ($total_taxable['medical'] < 0) {
    $total_taxable['medical'] = 0;
  }

  $total_taxable['total'] = $total_salary['basic'] + $total_taxable['house_rent'] + $total_taxable['medical'] + $total_taxable['conveyance'];
  $total_taxable['total'] = ceil($total_taxable['total']);

  $total_taxable['total'] = ceil($total_taxable['total']);
  $total_taxable['bonus'] = 0;

  return $total_taxable;
}

/** Income */
$medical_label = '';
$total_salary = ['gross' => 0, 'basic' => 0, 'house_rent' => 0, 'conveyance' => 0, 'medical' => 0];
$total_taxable = ['gross' => 0, 'basic' => 0, 'house_rent' => 0, 'conveyance' => 0, 'medical' => 0, 'bonus' => 0];
// sum salary
foreach ($salary['salary'] as $key => $value) {
  $total_salary['gross'] += $value['gross'];
  $total_salary['basic'] += $value['basic'];
  $total_salary['house_rent'] += $value['house_rent'];
  $total_salary['conveyance'] += $value['conveyance'];
  $total_salary['medical'] += $value['medical'];
}

$total_taxable = taxable_income($total_salary, $config);
$total_taxable['total_wo_bonus'] = $total_taxable['total'];

// add bonus to income
foreach ($salary['bonus'] as $bon) {
  $total_taxable['bonus'] += $bon;
}
$total_taxable['total'] = $total_taxable['total'] + $total_taxable['bonus'];

/** Tax Calculation */
$tax = tax_from_taxable_income($total_taxable['total'], $config['slab']);
$tax_payable = 0;
foreach ($tax as $key => $val) {
  $tax_payable += $val['tax'];
}

/** rebate */
$rebate_max = ($total_taxable['total'] * 25) / 100;
$inv = (250000 * 15) / 100;
$reb2 = $rebate_max - 250000;
$inv2 = ($reb2 * 12) / 100;

$total_rebate = $inv + $inv2;
$tax_w_rebate = $tax_payable - $total_rebate;
$tax_remain = $tax_w_rebate - $salary['tax_paid'];
$tax_remain_monthly = $tax_remain / $config['months_left'];

?>
<form></form>
<table cellpadding="5" border="1" style="border-collapse:collapse">
    <tr>
        <td>Month</td>
        <td>Gross</td>
        <td>Basic</td>
        <td>House Rent</td>
        <td>Conveyance</td>
        <td>Medical</td>
        <td>Phone</td>
        <td>Other</td>
    </tr>
  <?php
  foreach ($salary['salary'] as $key => $value) {
    ?>
      <tr>
          <td><?php echo $key; ?></td>
          <td class="gross"><?php echo $value['gross']; ?></td>
          <td class="basic"><?php echo $value['basic']; ?></td>
          <td class="house"><?php echo $value['house_rent']; ?></td>
          <td class="conveyance"><?php echo $value['conveyance']; ?></td>
          <td class="medical"><?php echo $value['medical']; ?></td>
          <td class="phone"><?php echo $value['phone']; ?></td>
          <td class="other"><?php echo $value['other']; ?></td>
      </tr>
    <?php
  }
  ?>
    <tr>
        <td>Total</td>
        <td><?php echo $total_salary['gross']; ?></td>
        <td><?php echo $total_salary['basic']; ?></td>
        <td><?php echo $total_salary['house_rent']; ?></td>
        <td><?php echo $total_salary['conveyance']; ?></td>
        <td><?php echo $total_salary['medical']; ?></td>
        <td>0</td>
        <td>0</td>
    </tr>

</table>
<br/>
<hr/>
<br/>

<h2>Income Calculation</h2>
<table cellpadding="5" border="1" style="border-collapse:collapse">
    <tr>
        <td>Income Breakdown</td>
        <td>Gross Annual Income</td>
        <td>Less Exempted</td>
        <td>Taxable Income</td>
    </tr>
    <tr>
        <td>Basic</td>
        <td><?php echo $total_salary['basic']; ?></td>
        <td> -</td>
        <td><?php echo $total_salary['basic']; ?></td>
    </tr>
    <tr>
        <td>House Rent Allowance</td>
        <td><?php echo $total_salary['house_rent']; ?></td>
        <td> (300,000)</td>
        <td><?php echo $total_taxable['house_rent']; ?></td>
    </tr>
    <tr>
        <td>Medical Allowance</td>
        <td><?php echo $total_salary['medical']; ?></td>
        <td><?php echo $medical_label; ?></td>
        <td><?php echo $total_taxable['medical']; ?></td>
    </tr>
    <tr>
        <td>Conveyance</td>
        <td><?php echo $total_salary['conveyance']; ?></td>
        <td> (30,000)</td>
        <td><?php echo $total_taxable['conveyance']; ?></td>
    </tr>
    <tr>
        <td>Holiday</td>
        <td><?php echo $total_taxable['bonus']; ?></td>
        <td> -</td>
        <td><?php echo $total_taxable['bonus']; ?></td>
    </tr>
    <tr style="background:palegreen;">
        <td><strong>Total</strong></td>
        <td><?php echo $total_salary['gross']; ?></td>
        <td> -</td>
        <td><strong><?php echo number_format($total_taxable['total'], 2); ?></strong></td>
    </tr>

</table>

<h2>Tax Calculation </h2>
<table cellpadding="5" border="1" style="border-collapse:collapse">
    <tr>
        <td>Slab</td>
        <td>Income</td>
        <td>% of Tax</td>
        <td>Tax Amount BDT</td>
    </tr>
  <?php

  foreach ($tax as $key => $val) {
    ?>
      <tr>
          <td><?php echo $key; ?></td>
          <td><?php echo $val['inc']; ?></td>
          <td><?php echo $val['per']; ?>%</td>
          <td><?php echo $val['tax']; ?></td>
      </tr>
    <?php
  }
  ?>
    <tr style="background:orchid;">
        <td colspan="3">Total Tax</td>
        <td><strong><?php echo number_format($tax_payable, 2); ?></strong></td>
    </tr>
</table>

<h2>Investment rebate</h2>
<?php
echo sprintf("Eligible amount for rebate (25%% of total income) BDT %s <br/>", $rebate_max);
?>
<table cellpadding="5" border="1" style="border-collapse:collapse">
    <tr>
        <td>First 15%</td>
        <td> 250,000</td>
        <td><?php echo $inv; ?></td>
    </tr>
    <tr>
        <td>Rest 12%</td>
        <td><?php echo $reb2; ?></td>
        <td><?php echo $inv2; ?></td>
    </tr>
    <tr style="background:powderblue;">
        <td colspan="2">Total Rebate:</td>
        <td> <?php echo $total_rebate; ?></td>
    </tr>
</table>
<h2>Monthly Deductible </h2>
<table cellpadding="5" border="1" style="border-collapse:collapse">
    <tr>
        <td>Total Tax - Rebate = Tax Excluding Rebate:</td>
        <td><?php echo number_format($tax_w_rebate); ?></td>
    </tr>
    <tr>
        <td>Tax Excluding Rebate - Already Paid(<?php echo $salary['tax_paid']; ?>) = Remaining :</td>
        <td> <?php echo number_format($tax_remain); ?></td>
    </tr>
    <tr style="background:dodgerblue;">
        <td> Remaining / <?php echo $config['months_left']; ?> = Monthly:</td>
        <td><?php echo number_format($tax_remain_monthly, 2); ?></td>
    </tr>
</table>