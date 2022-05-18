#include <HttpClient.h>
#include <InternetButton.h>
#include <math.h>

HttpClient http;
http_header_t headers[] = {
    //  { "Content-Type", "application/json" },
    //  { "Accept" , "application/json" },
    { "Accept" , "*/*"},
    { NULL, NULL } // NOTE: Always terminate headers will NULL
};
http_request_t request;
http_response_t response;

InternetButton ib = InternetButton();
int curr_pos = -1;
int last_pos = -1;
bool pos_locked = false;
int pos_counter = 0;
int network_counter = 0;

String API_CODE = ""; // unique code for specific user
String SERVER_ADDRESS = ""; // where your php server is located

void setup()
{
    ib.begin();
}

// basic non-robust logic
// at the moment it's only setup to actually work with position 1
// see GetPosition for info on positions
void loop()
{
    int pos = GetPosition();
    if(pos_locked)
    {
        if(pos == 1)
        {
            if (network_counter % 60 == 0)
            {
                int hours = (int) floor(HoursToday(1));
                NumLedsOn(hours);
                
            }
        }
        network_counter ++;
    }
    // what orientation is the particle, has it changed

    if (pos != curr_pos || pos != last_pos)
    {
        if (pos != last_pos)
        {
            pos_counter = -1;
        }
        
        if (pos_counter < 5)
        {
            SetLeds(pos, 50, 0, 0);
            pos_locked = false;
        }
        else
        {
            if (pos == 1)
            {
                StartTimer(1);
            }
            else
            {
                if(curr_pos == 1)
                {
                    StopTimer(1);
                }
            }
            
            SetLeds(pos, 0, 50, 0);
            pos_counter = -1;
            pos_locked = true;
            
            curr_pos = pos;
            network_counter = 0;
        }
        pos_counter ++;
        last_pos = pos;
    }
    delay(1000);
}

// Check the accelerometer and determine our orientation
int GetPosition()
{
    // we have 5 positions
    //  0     1  2  3   4
    // -90, -45, 0, 45, 90 degrees
    int pos = -1;
    
    int xValue = ib.readX();
    int yValue = ib.readY();
    
    if (xValue < 15 && xValue > -15)
    {
        if (yValue < -15)
        {
            pos = 2;
        }
    }
    
    if (yValue < 15 && yValue > -15)
    {
        if (xValue > 15)
        {
            pos = 4;
        }
        if (xValue < -15)
        {
            pos = 0;
        }
    }
    
    if (yValue < -15 && xValue > 15)
    {
        pos = 3;
    }
    if (yValue < -15 && xValue < -15)
    {
        pos = 1;
    }
    
    return pos;
}

// as the device is rotated, use this function to light up the LEDs at
// the 'bottom' of the device
void SetLeds(int pos, int r, int g, int b)
{
    ib.allLedsOff();
    switch (pos)
    {
        case 0:
            ib.ledOn(8, r, g, b);
            ib.ledOn(9, r, g, b);
            ib.ledOn(10, r, g, b);
            break;
        case 1:
            ib.ledOn(7, r, g, b);
            ib.ledOn(8, r, g, b);
            break;
        case 2:
            ib.ledOn(5, r, g, b);
            ib.ledOn(6, r, g, b);
            ib.ledOn(7, r, g, b);
            break;
        case 3:
            ib.ledOn(4, r, g, b);
            ib.ledOn(5, r, g, b);
            break;
        case 4:
            ib.ledOn(2, r, g, b);
            ib.ledOn(3, r, g, b);
            ib.ledOn(4, r, g, b);
            break;
        default:
            ib.ledOn(1, r, g, b);
            ib.ledOn(2, r, g, b);
            ib.ledOn(10, r, g, b);
            ib.ledOn(11, r, g, b);
            break;
    }

}

// turn num of LEDs on
// always turns on first LED, skips the second.
// all LEDS are blue.
// If more than 8 hours have been worked the 8th LED is green
// If more than 9 hours have been worked, the 9th LED is red
void NumLedsOn(int num)
{
    ib.allLedsOff();
    ib.ledOn(1, 0, 0, 50);
    
    if (num > 8)
    {
        num = 8;
        ib.ledOn(11, 255, 0, 0);
    }
    
    for (int i = 0; i < num; i++)
    {
        if(i==7)
        {
            ib.ledOn(3+i, 0, 50, 0);
        }
        else
        {
            ib.ledOn(3+i, 0, 0, 50);
        }
    }
}

// send a request to the server.
// return true if success
bool SendRequest(String path)
{
	request.hostname = SERVER_ADDRESS;
	request.port = 80;
	request.path = path;
	http.get(request, response, headers);
	
	if (response.status == 200)
	{
		return true;
	}
	else
	{
		return false;
	}  
}

// send a start command to the server
// not robust, no confirmations.
void StartTimer(int tag)
{
    bool res = SendRequest("/toggl/start.php?code=" + API_CODE + "&tag=" + String(tag));
    if (res)
    {
    }
}

// send a stop command to the server
// not robust, no confirmations.
void StopTimer(int tag)
{
    bool res = SendRequest("/toggl/stop.php?code=" + API_CODE + "&tag=" + String(tag));
    if (res)
    {
        
    }
}

// return True if the timer is currently running on the server
bool TimerRunning(int tag)
{
    bool res = SendRequest("/toggl/running.php?code=" + API_CODE + "&tag=" + String(tag));
    if (res)
    {
        return bool(response.body.toInt());
    }
    return false;
}

// from the server get the number of seconds worked and translate that to an int in hours
float HoursToday(int tag)
{
    bool res = SendRequest("/toggl/today.php?code=" + API_CODE + "&tag=" + String(tag));
    if (res)
    {
        String resp = response.body;
        float secs = response.body.toFloat();
        float hours = secs * 2.7777777777e-4; // 1/3600
        return hours;
    }
    return 0;
}

