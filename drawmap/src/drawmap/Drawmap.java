package drawmap;

import java.awt.Color;
import java.awt.Graphics2D;
import java.awt.image.BufferedImage;
import java.io.File;
import java.io.FileNotFoundException;
import java.io.IOException;
import java.io.RandomAccessFile;
import java.nio.MappedByteBuffer;
import java.nio.channels.FileChannel;
import java.sql.*;
import javax.imageio.ImageIO;
import radio.P1546;
import sun.nio.ch.DirectBuffer;

public class Drawmap {

    final static String path = System.getProperty("path", "");

    public static void main(String[] args) throws FileNotFoundException, IOException, SQLException {

        double TOP_LAT = 57.407823;
        double BOTTOM_LAT = 52.800651;
        double LEFT_LON = 21.008818;
        double RIGHT_LON = 26.663818;

        int w = 4500;
        int h = 4500;

        int x, y;

        float projection[][];

        Connection db = DB();

        PreparedStatement stmt;
        ResultSet rs;

        PreparedStatement stmt2;
        ResultSet rs2;

        short channel;
        short transmitter_id;
        short frequency;

        MappedByteBuffer map;

        stmt = db.prepareStatement("SELECT channel, frequency FROM transmitters "
                + "GROUP BY channel");

        if (stmt.execute()) {
            rs = stmt.getResultSet();
            while (rs.next()) {

                // Lets clear projection array values from previuos channels
                projection = new float[w + 1][h + 1];
                
                channel = rs.getShort(1);
                frequency = rs.getShort(2);
                stmt2 = db.prepareStatement("SELECT id FROM transmitters"
                        + " WHERE channel = " + channel);

                if (stmt2.execute()) {
                    rs2 = stmt2.getResultSet();

                    while (rs2.next()) {
                        transmitter_id = rs2.getShort(1);
                        FileChannel in = new RandomAccessFile(path + "/EML/" + transmitter_id + ".dat", "r").getChannel();
                        map = in.map(FileChannel.MapMode.READ_ONLY, 0, (int) in.size());

                        int i = 0;
                        for (double LON = LEFT_LON; LON <= RIGHT_LON; LON = LON + 0.001) {
                            for (double LAT = BOTTOM_LAT; LAT <= TOP_LAT; LAT = LAT + 0.001) {

                                x = (int) Math.round((w * (LON - LEFT_LON) / (RIGHT_LON - LEFT_LON)));
                                y = (int) Math.round((h * (LAT - TOP_LAT) / (BOTTOM_LAT - TOP_LAT)));

                                float E = map.getFloat();

                                if (projection[x][y] < E) {
                                    projection[x][y] = E;
                                }

                            }
                        }
                        // Cleaning map buffer
                        sun.misc.Cleaner cleaner = ((DirectBuffer) map).cleaner();
                        cleaner.clean();
                        in.close();
                    }
                }
                createimg(projection, w, h, frequency, channel);
            }
        }
    }

    static void createimg(float[][] projection, int w, int h, short frequency,
            short channel) {

        BufferedImage bufferedImage = new BufferedImage(w, h, BufferedImage.TYPE_INT_ARGB);
        Graphics2D g2d = bufferedImage.createGraphics();

        float E;
        for (int x = 0; x < w + 1; x++) {
            for (int y = 0; y < h + 1; y++) {

                E = projection[x][y];

                if (E < 20) {
                    continue;
                }

                double E_area = P1546.Earea(E, 99, 600, 1);
                double E_area2 = P1546.Earea(E, 75, 600, 1);


                if (E_area > 32) {
                    g2d.setColor(new Color(127, 255, 0, 150));
                    g2d.fillRect(x, y, 1, 1);
                } else if (E_area2 > 32) {
                    g2d.setColor(new Color(255, 255, 0, 150));
                    g2d.fillRect(x, y, 1, 1);
                }
            }
        }

        g2d.dispose();

        try {
            System.out.println("Writing "+path + "/TITLES/" + channel + ".png");
            File outputfile = new File(path + "/TITLES/" + channel + ".png");
            ImageIO.write(bufferedImage, "png", outputfile);
        } catch (IOException e) {
            System.out.println(e);
        }
    }

    static Connection DB() {
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