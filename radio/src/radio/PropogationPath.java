/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
package radio;

import java.io.*;
import static java.lang.Math.round;
import java.util.logging.Level;
import java.util.logging.Logger;


public class PropogationPath extends Thread  {

    public Point txPoint;
    
    public short f;
    public short channel;
    public short transmitter_id;
    public float hb;
    public float[] erps;
    
    PropogationPath(Point txPoint, short f, short channel, short transmitter_id, float hb, float[] erps ) {
        
        this.f = f;
        this.channel = channel;
        this.transmitter_id = transmitter_id;

        this.hb = hb;
        this.erps = erps;
        
        this.txPoint = txPoint;
        
    }
    
 
    @Override
    public void run()  {
        DataOutputStream os = null;
        
            double TOP_LAT = 57.407823;
            double BOTTOM_LAT = 52.800651;
            double LEFT_LON = 21.008818;
            double RIGHT_LON = 26.663818;
            
            
            int LON_DONE = (int) ((RIGHT_LON - LEFT_LON)/0.001);
            int LAT_DONE = (int) ((TOP_LAT - BOTTOM_LAT)/0.001);
            // Percent counting stuff
            int aaa = 1;
            int total = LAT_DONE * LON_DONE;
            int last = 0;
            int percent;
        
        try {
            String path = System.getProperty("path", "");
            os = new DataOutputStream(new BufferedOutputStream(new FileOutputStream(
path+"/EML/"+transmitter_id+".dat")));
            
               
            // TX antenna height
            double h1;
            // Receiver antenna height
            double h2 = 10;
            // Receiver antenna terrain clearance angle
            double tca;
            // Emedian
            double E;
            // Rx antenna effective heights 0-360 degres
            double[] heff = new double[361];
            
            // Percent time value
            double time = 50;
            
            for(double LON = LEFT_LON; LON <= RIGHT_LON; LON=LON+0.001) {

                for(double LAT = BOTTOM_LAT; LAT <= TOP_LAT; LAT=LAT+0.001) {

                    percent = ((aaa++)*100)/total;   
                    if(last != percent) {
                        System.out.println(percent+"%");
                            last = percent;
                    }

                    Point rxPoint = new Point(new Lat(LAT), new Lon(LON));

                    double distance = txPoint.distance(rxPoint);
                    double bearing =  txPoint.getInitialBearing(rxPoint);
                    
                    int bearing_abs;
                  
                    if(bearing < 0 ) {
                        bearing_abs = (int) round(360 + bearing);
                    }
                    else {
                        bearing_abs = (int) round(bearing);
                    }
                    
                    float erp;
                    if((bearing_abs/10) == 36) {
                        erp = erps[0];
                    }
                    else {
                        erp = erps[round(bearing_abs/10)]; 
                    }
                        
                    
                    if(distance >= 15) {
                        // Huj znajit a valit
                        if(heff[bearing_abs] == 0) {
                            h1 = Terrain.effectiveHeight(txPoint, bearing_abs, 15, hb);                      
                            heff[bearing_abs] = h1;
                            
                        }
                        else {
                            h1 = heff[bearing_abs];
                        }
                    }
                    else {
                        h1 = Terrain.effectiveHeight(txPoint, bearing, distance, hb);
                    }
                    

                    E = P1546.E(time, f, h1, distance)+(erp-30);
                     

                    double c2 = P1546.receiverCorrection(h2, f);
                    
                    double c1;
                    
                    if(E > 0 ) {
                        tca = Terrain.clearanceAngle(rxPoint, bearing, distance, h2);
                        c1 = P1546.TCACorrection(tca, f);
                    } 
                    else {
                        c1 = 0;
                    }
                    
                    E = E + c1 + c2;
                    
                    if(E < 0 ) {
                       E = 0;
                    }

                    try {
                        os.writeFloat((float)E);
                    } catch (IOException ex) {
                        Logger.getLogger(PropogationPath.class.getName()).log(Level.SEVERE, null, ex);
                    }
                    
                        
                } // End of lat loop 
            } // End of lon loop
        } catch (FileNotFoundException ex) {
            Logger.getLogger(PropogationPath.class.getName()).log(Level.SEVERE, null, ex);
        } finally {
            try {
                os.close();
            } catch (IOException ex) {
                Logger.getLogger(PropogationPath.class.getName()).log(Level.SEVERE, null, ex);
            }
        }
        
    }
}
