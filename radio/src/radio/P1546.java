package radio;

import java.io.File;
import java.io.IOException;
import static java.lang.Math.*;
import java.util.Locale;
import java.util.Scanner;

public class P1546 {
     
     final static double[] distanceRows = {1,  2,  3,  4,  5,  6,  7,  8,  9, 10, 11, 
         12,13,14, 15, 16, 17, 18, 19, 20, 25, 30, 35, 40, 45, 50,55, 60, 65, 
         70, 75, 80, 85, 90, 95, 100, 110, 120, 130,140, 150, 160, 170, 180, 
         190, 200, 225, 250, 275, 300, 325, 350,375, 400, 425, 450, 475, 500, 
         525, 550, 575, 600, 625, 650, 675,700, 725, 750, 775, 800, 825, 850, 
         875, 900, 925, 950, 975, 1000};
        
     final static double[] heightColumns={10, 20, 37.5, 75, 150, 300, 600, 1200, 0};
     final static double[] freqCurves = {100, 600, 2000};
     final static double[] timeCurves = {1, 10, 50};
     static double[][][][] propCurves = new double[3][3][][];
        
    static double E(double t, double f, double h, double d) {
        
        // If distance shorter than 0.1km
         if(d < 0.1) {
             return Efs(d);
         }
         // For distances shorter than 1km, but longer than 0.1km
         else if(d < 1) {
             double E_1km = E(t, f, h, 1);
             return Efs(0.1)+(E_1km-Efs(0.1))*Math.log10(d/0.1);
         }
        
         int t_inf_index = Utils.near(timeCurves,t);
         int f_inf_index = Utils.near(freqCurves, f);
         int h_inf_index = Utils.near(heightColumns, h);
         int d_inf_index = Utils.near(distanceRows, d);
           
         int[] times;
         int[] frequencies;
         int[] heights;
         int[] distances;
         
         
         if(timeCurves[t_inf_index] == t)
            times = new int[]{t_inf_index};
         else
            times = new int[]{t_inf_index, t_inf_index+1};
         
         if(freqCurves[f_inf_index] == f)
             frequencies = new int[]{f_inf_index};
         else
             frequencies = new int[]{f_inf_index, f_inf_index+1};
         
         if(heightColumns[h_inf_index] == h)
             heights = new int[]{h_inf_index};
         else
             heights = new int[]{h_inf_index, h_inf_index+1};
         
         if(distanceRows[d_inf_index] == d)
             distances = new int[]{d_inf_index};
         else
             distances = new int[]{d_inf_index, d_inf_index+1};
         
         // data[i][j][k][l]
         double [][][][] data;
         
         data = new double[times.length][][][];
         for(int i=0; i < times.length; i++) {
             data[i] = new double[frequencies.length][][];
             for(int j=0; j<frequencies.length; j++) {
                 data[i][j] = new double[heights.length][];
                 for(int k=0; k<heights.length;k++) {
                     data[i][j][k] = new double[distances.length];
                     for(int l=0; l<distances.length;l++) {
                         data[i][j][k][l] = propCurves[frequencies[j]][times[i]][distances[l]][heights[k]];
                     }
                     
                     if(distances.length==2) {
                        data[i][j][k][0]=interpolate(d, distanceRows[distances[0]], distanceRows[distances[1]],
                                data[i][j][k][0], data[i][j][k][1]);
                    }
                 }
                 if(heights.length==2) {
                        data[i][j][0][0]=interpolate(h, heightColumns[heights[0]], heightColumns[heights[1]],
                                data[i][j][0][0], data[i][j][1][0]);
                }
             }
             
             if(frequencies.length==2) {
                 data[i][0][0][0] = interpolate(f, freqCurves[frequencies[0]], freqCurves[frequencies[1]],
                         data[i][0][0][0], data[i][1][0][0]);
             }
             
         }
         
         if(times.length == 2) {
             data[0][0][0][0] = interpolate2(t, timeCurves[times[0]], timeCurves[times[1]],
                        data[0][0][0][0], data[1][0][0][0]
                     );
         }
         
         return data[0][0][0][0];
    }
        
    static void loadPropCurves() {
        
        String path = System.getProperty("path", "");
        
        propCurves[0][0] = readCSV(path+"/P1546/100_1_land.csv");
        propCurves[0][1] = readCSV(path+"/P1546/100_10_land.csv");
        propCurves[0][2] = readCSV(path+"/P1546/100_50_land.csv");
        
        propCurves[1][0] = readCSV(path+"/P1546/600_1_land.csv");
        propCurves[1][1] = readCSV(path+"/P1546/600_10_land.csv");
        propCurves[1][2] = readCSV(path+"/P1546/600_50_land.csv");
        
        propCurves[2][0] = readCSV(path+"/P1546/2000_1_land.csv");
        propCurves[2][1] = readCSV(path+"/P1546/2000_10_land.csv");
        propCurves[2][2] = readCSV(path+"/P1546/2000_50_land.csv");
    }
    
    static double[][] readCSV(String file) {    
        double [][] ret=new double[distanceRows.length][heightColumns.length];
        try
        {
            Scanner fileScan = new Scanner (new File(file));
            fileScan.useLocale(Locale.US);
            fileScan.useDelimiter("(;|\r?\n|\r)+");
    
            for(int y=0;y<distanceRows.length;y++) {
                // Skiping first line
                if(y==0)
                   fileScan.nextLine();
                
                for(int x=0;x<heightColumns.length;x++)  {
                    // Skip first coll
                    if(x == 0)
                        fileScan.next();
                     
                     ret[y][x]=fileScan.nextDouble();                  
                }
            }
            fileScan.close();            
        }
        catch (IOException e)
        {
            e.printStackTrace();
        } 
        return ret;
    }
    
    /**
    * @TESTED P.1546 28 graph
    * Calculates correction based on clearance angle on receiver site 
    * Calculates terrain clearance angle from receiver site up to 16km, but not going beyong transmitter.
    * @param tca terrain clearance angle
    * @param f frequency MHz 
    * @return correction value in dB
    */
    static double TCACorrection(double tca, double f) {
			
	// tca should be limited such that it is not less than +0.55° or more than +40.0°.
	if(tca < 0.55 ) {
            tca = 0.55;
	}
	if(tca > 40) {
            tca = 40;
	}
		
	return J(0.036*sqrt(f)) - J(0.065*tca*sqrt(f));
    }
    
    static double interpolate(double val, double inf, double sup, double E_inf, double E_sup) {
	return E_inf+(E_sup-E_inf)*Math.log10(val/inf)/log10(sup/inf);
    }
    
    static double interpolate2(double t, double Qinf, double Qsup, double Einf, double Esup) {
        
        Qinf = Qi(Qinf/100);
        Qsup = Qi(Qsup/100);
        t = t/100;
        return Esup*(Qinf-t)/(Qinf-Qsup)+Einf*(t-Qsup)/(Qinf-Qsup);   
    }
    
    static double receiverCorrection(double h2, double f) {
        // For non urban locations
        short R = 10;
        double Kh2 = 3.2 + 6.2*log10(f);
        return Kh2*log10(h2/R);
    }

    static double Qi(double x) {
        if(x <= 0.5) {
            return T(x) - G(x);
        }
	else {
            return -(T(1-x)-G(1-x));
	}
    }

    static double T(double x) {
        return Math.sqrt(-2*Math.log(x));
    }
    
    static double Lb(double E, double f) {
        return 139.3 - E + 20*log10(f);
    }
    // this method should be accesible out of package
    static public double Earea( double e_median, double percent, double f, double K) {
      double o = K + 1.3*Math.log10(f);
      return e_median + Qi(percent/100)*o;
    }
  
    static double Efs(double d) {
        return 106.9 - 20*Math.log10(d);
    }
	
    static double G(double x) {
        double c0 = 2.515517;
        double c1 = 0.802853;
        double c2 = 0.010328;
        double d1 = 1.432788;
        double d2 = 0.189269;
        double d3 = 0.001308;
        return (((c2*T(x)+c1)*T(x))+c0)/( ((d3*T(x)+d2)*T(x)+d1)*T(x)+1 );
    }
    
    static double J(double v) {
	return 6.9+20*log10(sqrt(pow(v-0.1,2)+1)+v-0.1);
    }
}