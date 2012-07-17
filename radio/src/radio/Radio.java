package radio;

import java.io.IOException;
import java.sql.Connection;
import java.sql.DriverManager;
import java.sql.PreparedStatement;
import java.sql.ResultSet;

public class Radio {

    /**
     * @param args the command line arguments
     */
    public static void main(String[] args) throws IOException {


        P1546.loadPropCurves();
        Connection db = DB();

        short transmitter_id;
        short channel;
        short frequency;
        float tx_height;
        float lat;
        float lon;

        float erps[] = new float[36];

        try {
            PreparedStatement stmt;
            ResultSet rs;

            PreparedStatement stmt2;
            ResultSet rs2;

            // channel 36;
            stmt = db.prepareStatement("SELECT id, channel, frequency, height, "
                    + "lat, lon FROM transmitters WHERE simulate = 1");

            if (stmt.execute()) {
                rs = stmt.getResultSet();

                while (rs.next()) {
                    transmitter_id = rs.getShort(1);
                    channel = rs.getShort(2);
                    frequency = rs.getShort(3);
                    tx_height = rs.getFloat(4);
                    lat = rs.getFloat(5);
                    lon = rs.getFloat(6);


                    stmt2 = db.prepareStatement("SELECT azimuth, erp FROM erps "
                            + "WHERE transmitter_id = " + transmitter_id);

                    stmt2.execute();
                    rs2 = stmt2.getResultSet();


                    while (rs2.next()) {
                        int azimuth = rs2.getShort(1);
                        float erp = rs2.getFloat(2);
                        erps[azimuth / 10] = erp;
                    }

                    Point txPoint = new Point(new Lat(lat), new Lon(lon));
                    PropogationPath tx = new PropogationPath(txPoint, frequency, channel, transmitter_id, tx_height, erps);
                    tx.start();
                }

                rs.close();
                stmt.close();
            }


        } catch (Exception e) {
            e.printStackTrace();
        }

    }

    public static Connection DB() {
        Connection conn = null;
        String url = "jdbc:mysql://localhost:3306/";
        String dbName = "baigiamasis_v4";
        String driver = "com.mysql.jdbc.Driver";
        String userName = "root";
        String password = "";

        try {
            PreparedStatement stmt = null;
            ResultSet rs = null;
            Class.forName(driver).newInstance();
            conn = DriverManager.getConnection(url + dbName, userName, password);
            System.out.println("Connected to the database");

        } catch (Exception e) {
            e.printStackTrace();
        }
        return conn;
    }
}