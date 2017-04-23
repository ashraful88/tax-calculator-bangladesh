# Tax Calculator Bangladesh
Income Tax Calculator for salaried employees in Bangladesh

#Setup Your Income
Enter Salary of july to june
   "salary": {
    "1": {
      "gross": 200000,
      "basic": 100000,
      "house_rent": 50000,
      "conveyance": 10000,
      "medical": 20000,
      "phone": 0,
      "other": 0
    },
    "2": {
      "gross": 200000,
      "basic": 100000,
      "house_rent": 50000,
      "conveyance": 10000,
      "medical": 20000,
      "phone": 0,
      "other": 0
    },

here "1" => month july, "2" => month Aug ..... "12"=> June 


#Configure
Open Configuration file 'config.json'
Update tex numbers base on National Board of Revenue (NBR), Bangladesh
  "medical_basic_per" = max % of medical allowance allowed of basic salary.  
  "medical_limit" = max limit of medical allowance.
  "conveyance_limit" = max limit of conveyance allowance.
  "house_rent_limit" = max limit of house rent allowance.
  "months_left" = number of month left till next june 
  "slab" => "inc" = Income slabs, "per" = % of tax applicable by slab
