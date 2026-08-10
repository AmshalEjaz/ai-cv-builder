# AI CV Builder - CV extraction/rendering fixes

This build fixes the following:

- Skills are recovered from the uploaded CV when Ollama returns null/empty skills.
- Name, email, phone, location and professional title use source CV values as the authority.
- Generic/hallucinated summaries are rejected in favour of the source Profile/Summary section.
- Rendered CV data no longer loses skills/experience/education when AI fields are null/empty.
- PDF columns use border-box sizing so padding cannot push columns beyond the page width.
- The PDF template includes the extracted location in contact information where applicable.
- Professional title extraction recognizes Full Stack Developer/Engineer and related titles.
- Existing template layouts are preserved; no template image/mockup is used as user data.

Important: re-upload or re-process an existing CV after installing this build so the database receives the corrected structured data.
