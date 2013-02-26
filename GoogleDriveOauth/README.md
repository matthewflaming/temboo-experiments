###Google Drive OAuth with the Temboo SDK
This example demostrates using the Temboo SDK to build a simple Node.js app that performs Google Drive authentication,
then uses the retrieved oAuth token to list Google Drive files and revision history. 

###Quickstart
 1. Sign up for a free account at https://www.temboo.com
 2. Download the Temboo Node.js SDK from https://www.temboo.com/download
 3. Sign up for a Google Drive account 
 4. Register a new Google App client ID at https://code.google.com/apis/console/
 6. Edit the constants defined in googleOauthAndDrive.js to include your Temboo account information, Google Drive client ID and secret, and the URL where the page can be accessed
 7. Run "node googleOauthAndDrive.js"
 8. Browse to http://localhost:8080 (or wherever your Node.js install serves pages)

###Copyright and License
Licensed under the Apache License, Version 2.0 (the "License"); you may not use this file except in compliance with the License. You may obtain a copy of the License at

    http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software distributed under the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the License for the specific language governing permissions and limitations under the License.
