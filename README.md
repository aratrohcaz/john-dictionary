john-dictionary
---

# Brief
Generates Dictionary Files that can be used with password brute forcing software. Dictionary files are based off of a word list provided by the user.

### About/Background
This is a simple php-script for generating word-lists. It's goal was to get me out of trouble for a machine that I had previously set-up and had forgotten the password. Resetting it, or re-installing was not an option. I wrote in PHP because I needed something quick and dirty that I was familiar enough in to get the job done with minimal fluff.

Is there room for improvement? Absolutely, but I needed solutions, not artwork.

## How to Use
Run there `run.php` file a terminal. 
 - On the first run it should create the 2 directories if they do not exist (`input` and `dictionaries`).
 
It requires at least one text file (extension must be `txt`) in the `input` folder with words you think might be part of the password (case does not matter, but it will be tested as typed for just-in-case). A word per line.

Each successive run will create a new dictionary file and update the file `rip.sh`. `rip.sh` is just an easier way for calling `john` from the cli with the correct arguments, you'll want to make those changes inside `run.php`. 

``Note: Keep an eye on your disk usage. In my case I started with 9 words, making a rainbow for each word, rot13'd, and numbers 0 to 3000. This resulted in a 45MB dictionary file, it can very quickly add up``
