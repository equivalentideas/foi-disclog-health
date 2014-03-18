<?
//Department of Health
//18 March 2014
date_default_timezone_set('Australia/Sydney');

require '../scraper/scraperwiki.php';
require '../scraper/scraperwiki/simple_html_dom.php';

//Options
$department_name = "Department of Health";
$base_department_url = "http://www.health.gov.au";

$html = scraperwiki::scrape("http://www.health.gov.au/internet/main/publishing.nsf/Content/foi-disc-log");
$dom = new simple_html_dom();
$dom->load($html);

//Start the scrape
print("Beginning FOI Scrape of ". $department_name ." FOI log ...\n");

foreach($dom->find("table") as $data)
{
    foreach($data->find("tr") as $foi_table)
    {
      $foi_id = "";
      $foi_link = "";
      $release_date = "";
      $iso8601_release_date = "";
      $foi_title = "";
      $scrape_date = date('Y-m-d', strtotime("now"));
      $access_decision = "UNKNOWN";
      $i = "1";
      
      foreach($foi_table->find("td") as $individual_foi_info)
      {
        //Loop over each TD. The table's order is: 
        //FOI Number || Date of Access	|| Title of FOI Request (link to documents) ||	Access and Number of Documents	|| Other Information

        if ($i == 1)
        {
          //Reference number
          $foi_id = preg_replace('/[^(\x20-\x7F)]*/','', $individual_foi_info->innertext);
					$foi_id = trim($foi_id);
					$foi_id = strip_tags($foi_id);
					$foi_id = preg_replace('/\s+/', ' ',$foi_id);
        }
        if ($i == 2)
        {
          //Date
          $release_date_raw_text = str_replace('&nbsp;'," ",$individual_foi_info->plaintext);
          $release_date = trim($release_date_raw_text);
          $iso8601_release_date = date('Y-m-d', strtotime($release_date));    
        }
        if ($i == 3)
        {
         //Title
         $foi_title_raw_text = trim($individual_foi_info->plaintext);
         $foi_title_raw_text_clean = filter_var($foi_title_raw_text, FILTER_SANITIZE_STRING);
         $foi_title = preg_replace('/\s+/', ' ', $foi_title_raw_text_clean);
         //Link
         foreach ($individual_foi_info->find("a") as $link)
         {
         		$foi_link = "http://www.health.gov.au/internet/main/publishing.nsf/Content/foi-disc-log";
         }        
				}
        if ($i == 4)
        {
					//Access and number of documents
					//Ignore
        }
        if ($i == 5)
        {
					//Other information
					//Ignore
        }
        $i++;
      }
      
      //Create array
      $foi_array = array('foi_reference' => $foi_id, 'foi_title' => $foi_title, 'foi_link' => $foi_link, 'foi_release_date' => $release_date, 'iso8601_foi_release_date' => $iso8601_release_date, 'access_decision' => $access_decision, 'date_scraped' => $scrape_date);

      //Save the data
      $unique_keys = $foi_id;

      //Check to see if there is something worth inserting. If there isn't anything in the foi_reference array, we assume there's no data and skip it.
      if ($foi_array['foi_reference'] == "")
      {
        //empty
      }
      else
      {
        //Check to see if the record has already been inserted into the database.
        if (scraperwiki::get_var($foi_id) == "")
        {
          //No record found. Insert.
          print "New FOI release found. Inserting ".$foi_id."\n";
          scraperwiki::save_sqlite($unique_keys, $foi_array, 'data');
          scraperwiki::save_var($foi_id, $foi_id);
        }
        else 
        {
          //Record is found, so skip.
          print "Previous FOI found. Skipping.\n";
        }
      }  

			  
    }
}

?>

