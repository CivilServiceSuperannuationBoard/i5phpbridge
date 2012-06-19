import javax.servlet.*;

public class BetaSingletonFactory implements ServletContextListener {
    public static final String POOL = "betapool";
    public static final String META = "betameta";

    public void contextInitialized(ServletContextEvent event) {
        ServletContext ctx = event.getServletContext();
        ctx.setAttribute(POOL, new BetaPool());
        ctx.setAttribute(META, new BetaMetaData());
    }

    public void contextDestroyed(ServletContextEvent event) {
        ServletContext ctx = event.getServletContext();
        ctx.setAttribute(POOL, null);
        ctx.setAttribute(META, null);
    }

    public static BetaPool getPoolInstance(ServletContext ctx) {
        return (BetaPool)ctx.getAttribute(POOL);
    }

    public static BetaMetaData getMetaInstance(ServletContext ctx) {
        return (BetaMetaData)ctx.getAttribute(META);
    }
}
