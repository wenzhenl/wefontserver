//
//  ViewController.swift
//  TestAlyssaServer
//
//  Created by Wenzheng Li on 12/30/15.
//  Copyright © 2015 Wenzheng Li. All rights reserved.
//

import UIKit

class ViewController: UIViewController {

    override func viewDidLoad() {
        super.viewDidLoad()
        
    }
    
    @IBOutlet weak var button: UIButton!
    @IBAction func getdata() {
        requestDataFromServer()
    }
    func requestDataFromServer() {
        
        let params = NSMutableDictionary()
            
//        params["email"] = "liyukuang@gmail.com"
//        params["password"] = "12345"
//        params["nickname"] = "Leeyukuang"
        
        params["email"] = "henrydyc@hotmail.com"
        params["password"] = "happycoder"
        params["nickname"] = "henrydyc"
        
        // in case you need time
        params["last_modified_time"] = NSDate().timeIntervalSince1970
        
        let yourIP = "http://52.69.172.155/"
        let api_name = "alyssa_user_signup.php"
        Settings.fetchDataFromServer(self, errMsgForNetwork: "cannot access network", destinationURL: yourIP + api_name, params: params, retrivedJSONHandler: handleRetrivedFontData)
    
    }
    
    func handleRetrivedFontData(json: NSDictionary?) {
        if let parseJSON = json {
            
            // Okay, the parsedJSON is here, let's check if the font is still fresh
            if let success = parseJSON["success"] as? Bool {
                print("Success: ", success)
                let message = parseJSON["message"]
                print("message: ", message)
                if success {
                    
                    if let fontString = parseJSON["font"] as? String {
                        if let fontData = NSData(base64EncodedString: fontString, options: NSDataBase64DecodingOptions(rawValue: 0)) {
                            if let lastModifiedTime = parseJSON["lastModifiedTime"] as? Double {
                                print(fontData)
                                print(lastModifiedTime)
                            }
                        } else {
                            print("Failed convert base64 string to NSData")
                        }
                    } else {
                        print("cannot convert data to String")
                    }
                }
            }
        } else {
            print("Cannot fetch data")
        }
    }
}

