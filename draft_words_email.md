
Hi internals,

In 2010, the OWASP project reported that injection flaws(1) were the most common critical vulnerability in software. The OWASP project has spent over ten years trying to educate programmers about these types of vulnerabilities. 

In 2021, the most common critical vulnerability is ... still injection flaws.

I'm beginning to suspect that education of developers isn't the way to prevent injection vulnerabilities.

The crux of the problem is that any solution that requires programmers to be aware of, and remember to protect against, injection attacks is going to fail. Some programmers will be unaware, others will forget, and others will just not take the time to jump through the hoops required to make their code safe.   

Instead of education, a better solution is to make it possible for libraries to tell the difference between strings that are part of the code-base, and strings that have come from outside of the code. 

Once that is possible, it is possible to make APIs that are 'inherently safe'.


*insert example of how they are easy to use here*






There's a pretty good talk by Christoph Kern about how they implemented this 'literal tracking' idea in Java, and what their experience was rolling it out at scale:

https://www.youtube.com/watch?v=ccfEu-Jj0as&ab_channel=OWASP


cheers
Dan
Ack

1 - Injection flaws, such as SQL, OS, and LDAP injection, occur when untrusted data is sent to an
interpreter as part of a command or query. The attacker’s hostile data can trick the interpreter
into executing unintended commands or accessing unauthorized data.


