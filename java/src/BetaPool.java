import com.ibm.as400.access.*;
import java.util.Properties;
import java.io.InputStream;

public class BetaPool {
    private static AS400ConnectionPool i5Pool = null;
    private static int i5service = AS400.COMMAND;
    private static String i5system;
    private static String i5user;
    private static String i5pass;
    private static int i5total;
    private static Properties properties;

    static {
        setI5Source();
    }

    private static void setI5Source () {
        properties = new Properties();
        try {
            InputStream in = properties.getClass().getClassLoader().getResourceAsStream("i5phpbridge.properties");
            properties.load(in);
            in.close();
        } catch (Exception e) {
            e.printStackTrace();
        }

        i5system = properties.getProperty("betasystem");
        i5user = properties.getProperty("betauser");
        i5pass = properties.getProperty("betapassword");
        i5total = Integer.parseInt(properties.getProperty("betatotal"));

        try {
            i5Pool = new AS400ConnectionPool();
            i5Pool.fill(i5system, i5user, i5pass, i5service, i5total);
        } catch (ConnectionPoolException e) {
            e.printStackTrace();
        }
    }

    public static AS400 getConnection() {
        try {
            AS400 conn = i5Pool.getConnection(i5system, i5user, i5pass, i5service);
            return conn;
        } catch (Exception e) {
            e.printStackTrace();
            return null;
        }
    }

    public static void returnConnection(AS400 i5connection) {
        try {
            i5Pool.returnConnectionToPool(i5connection);
        } catch (Exception e) {
            e.printStackTrace();
        }
    }

    public Object clone()
        throws CloneNotSupportedException {
            throw new CloneNotSupportedException();
        }
}
